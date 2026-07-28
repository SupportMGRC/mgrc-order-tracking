@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-7">

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>Choose a COA Template
                    </h5>
                </div>

                <div class="card-body">

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <p class="text-muted">
                        No COA template has been set for
                        <strong>{{ $product->name }}</strong> yet, so please choose
                        which certificate to use for this order.
                    </p>

                    <p class="text-muted small">
                        To skip this step in future, set the COA template on the
                        product itself under Product Management.
                    </p>

                    <form method="POST"
                          action="{{ route('orders.coa.template', [$order->id, $product->id]) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="coa_template" class="form-label">COA Template</label>
                            <select name="coa_template" id="coa_template"
                                    class="form-select" required>
                                <option value="">— Select a template —</option>
                                @foreach ($templates as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                Continue
                            </button>
                            <a href="{{ route('orderdetails', $order->id) }}"
                               class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
