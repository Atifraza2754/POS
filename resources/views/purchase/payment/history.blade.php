@extends('layouts.app')
@section('title', __('purchase.payment.history'))

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
            'Purchase Payments',
            'Payment History',
        ]"/>

        <div class="card">
            @if (session('info'))
                <span class="alert alert-success">{{session('info')}}</span>
            @endif
            @if (session('error'))
                <span class="alert alert-danger">{{session('error')}}</span>
            @endif
            
            <div class="card-header px-4 py-3">
                <div class="row">
                    <div class="col-md-12">
                        <div>
                            <h5 class="mb-0 text-uppercase">{{ __('Purchase Payment History') }}</h5>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->role_id ==1)
                <div class="row g-3 mt-3">
                    @if(auth()->user()->role_id == 1)
                    <div class="col-md-4">
                        <x-label for="user_id" name="{{ __('user.user') }}" />
                        <x-dropdown-user selected="" :showOnlyUsername='true' />
                    </div>
                    @endif
                    <div class="col-md-4">
                        <x-label for="from_date" name="{{ __('app.from_date') }}" />
                        <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Payment Date"><i class="fadeIn animated bx bx-info-circle"></i></a>
                        <div class="input-group mb-3">
                            <x-input type="text" additionalClasses="datepicker-edit" name="from_date" :required="true" value=""/>
                            <span class="input-group-text" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <x-label for="to_date" name="{{ __('app.to_date') }}" />
                        <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Payment Date"><i class="fadeIn animated bx bx-info-circle"></i></a>
                        <div class="input-group mb-3">
                            <x-input type="text" additionalClasses="datepicker-edit" name="to_date" :required="true" value=""/>
                            <span class="input-group-text" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <form class="row g-3 needs-validation" id="datatableForm" action="{{ route('purchase.payment.delete') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf
                        @method('POST')
                        <table class="table table-striped table-bordered border w-100" id="datatable">
                            <thead>
                                <tr>
                                    <th class="d-none"></th> <!-- hidden ID -->
                                    <th><input type="checkbox"></th>
                                    <th>Supplier</th>
                                    <th>Mobile</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Remaining Amount</th>
                                    <th>Credit Limit</th>
                                    <th>Payment Date</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end page wrapper-->
@endsection

@section('js')
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('custom/js/common/common.js') }}"></script>
<script src="{{ asset('custom/js/purchase/purchase-payment-list.js') }}"></script>
@endsection
