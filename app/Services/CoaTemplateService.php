<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

/**
 * Resolves which COA template a product uses and who may work with COAs.
 */
class CoaTemplateService
{
    /** Sentinel meaning "this product never gets a COA". */
    public const NONE = 'none';

    /** All configured templates, keyed by template id. */
    public function all(): array
    {
        return config('coa_templates', []);
    }

    public function exists(?string $key): bool
    {
        return $key !== null
            && $key !== self::NONE
            && array_key_exists($key, $this->all());
    }

    public function get(?string $key): ?array
    {
        return $this->exists($key) ? $this->all()[$key] : null;
    }

    /**
     * Options for a template picker, as [key => label].
     */
    public function options(): array
    {
        return array_map(fn ($t) => $t['label'], $this->all());
    }

    /**
     * Options for the product admin form, including the two special states.
     *
     * Variant groups are collapsed to a single entry — a product is "MSC P2",
     * and QC decides per order whether that certificate carries the patient's
     * name.
     */
    public function productOptions(): array
    {
        return ['' => '— Not set (ask when generating) —']
            + $this->productChoices()
            + [self::NONE => 'No COA for this product'];
    }

    /**
     * Template choices for the product form, as [stored value => label], with
     * each variant group represented once by its default member.
     */
    public function productChoices(): array
    {
        $out = [];

        foreach ($this->all() as $key => $tpl) {
            $group = $tpl['variant_group'] ?? null;

            if ($group === null) {
                $out[$key] = $tpl['label'];
                continue;
            }

            // One entry per group, stored as the group's default member.
            if (!isset($out[$this->groupDefaultKey($group)])) {
                $out[$this->groupDefaultKey($group)] =
                    $tpl['variant_group_label'] ?? $tpl['label'];
            }
        }

        return $out;
    }

    /**
     * The value the product form should show as selected.
     *
     * A product saved as 'msc_p2_noname' still displays as "MSC P2", so an
     * existing row keeps working after the two entries were collapsed.
     */
    public function canonicalProductValue(?string $key): ?string
    {
        $group = $this->variantGroup($key);

        return $group ? $this->groupDefaultKey($group) : $key;
    }

    // Variant groups

    public function variantGroup(?string $key): ?string
    {
        return $this->get($key)['variant_group'] ?? null;
    }

    /**
     * Members of a template's variant group, as [key => variant label].
     * Empty when the template has no alternates.
     */
    public function variantsFor(?string $key): array
    {
        $group = $this->variantGroup($key);

        if ($group === null) {
            return [];
        }

        $out = [];

        foreach ($this->all() as $k => $tpl) {
            if (($tpl['variant_group'] ?? null) === $group) {
                $out[$k] = $tpl['variant_label'] ?? $tpl['label'];
            }
        }

        return count($out) > 1 ? $out : [];
    }

    public function hasVariants(?string $key): bool
    {
        return $this->variantsFor($key) !== [];
    }

    /**
     * The member a product defaults to, or the first one declared.
     */
    public function groupDefaultKey(string $group): ?string
    {
        $first = null;

        foreach ($this->all() as $k => $tpl) {
            if (($tpl['variant_group'] ?? null) !== $group) {
                continue;
            }

            $first ??= $k;

            if ($tpl['variant_default'] ?? false) {
                return $k;
            }
        }

        return $first;
    }

    /**
     * Whether two templates are alternate wordings of the same certificate.
     * This is the boundary Quality staff may move an order across; anything
     * wider stays with superadmins.
     */
    public function sameVariantGroup(?string $a, ?string $b): bool
    {
        $ga = $this->variantGroup($a);

        return $ga !== null && $ga === $this->variantGroup($b);
    }

    public function productHasCoa(Product $product): bool
    {
        return $product->coa_template !== self::NONE;
    }

    public function resolveForOrderLine(Order $order, Product $product): ?string
    {
        $line = $order->products()->where('product_id', $product->id)->first();

        if ($line && $this->exists($line->pivot->coa_template)) {
            return $line->pivot->coa_template;
        }

        return $this->exists($product->coa_template) ? $product->coa_template : null;
    }

    /**
     * May open the COA editor, print and download. Quality Control, Quality
     * Assurance and superadmins.
     */
    public function userMayAccess(?User $user): bool
    {
        return $user ? $user->canViewCoa() : false;
    }

    /**
     * May write: save fields, switch template, upload morphology or attach a
     * COA PDF. Quality Control and superadmins only — Quality Assurance is
     * read-only by design, so it can audit a certificate without altering it.
     */
    public function userMayEdit(?User $user): bool
    {
        return $user ? $user->canEditCoa() : false;
    }

    /**
     * Editable field keys for a template, in display order.
     */
    public function editableFields(?string $key): array
    {
        return $this->get($key)['editable'] ?? [];
    }

    public function fieldLabels(?string $key): array
    {
        return $this->get($key)['field_labels'] ?? [];
    }


    public function acceptsMorphologyImage(?string $key): bool
    {
        $tpl = $this->get($key);

        return isset($tpl['coordinates']['page2']['morphology_slot']);
    }

    public function pdfUrl(?string $key): ?string
    {
        $tpl = $this->get($key);

        if (!$tpl) {
            return null;
        }

        $relative = 'assets/pdf/' . $tpl['pdf'];
        $url      = asset($relative);
        $path     = public_path($relative);

        // is_file() guards against a template that has not been uploaded yet;
        // in that case the plain URL still renders the usual 404 in the editor.
        if (is_file($path) && ($mtime = @filemtime($path))) {
            $url .= '?v=' . $mtime;
        }

        return $url;
    }
}