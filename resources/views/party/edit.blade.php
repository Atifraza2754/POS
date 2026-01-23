@extends('layouts.app')
@section('title', $lang['party_update'])

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'party.parties',
                                            'party.list',
                                            'party.update_party',
                                        ]"/>
                <div class="row">
                    <div class="col-12 col-lg-12">
                        <div class="card">
                            <div class="card-header px-4 py-3">
                                <h5 class="mb-0">{{ __('party.details') }}</h5>
                            </div>
                            <div class="card-body p-4">
                                <form class="row g-3 needs-validation" id="partyForm" action="{{ route('party.update') }}" enctype="multipart/form-data">
                                    {{-- CSRF Protection --}}
                                    @csrf
                                    @method('PUT')

                                    <input type="hidden" name='party_id' value="{{ $party->id }}" />
                                    <input type="hidden" name="party_type" value="{{ $party->party_type }}">
                                    <input type="hidden" id="operation" name="operation" value="update">
                                    <input type="hidden" id="base_url" value="{{ url('/') }}">

                                  

                                    <div class="col-md-6">
                                        <x-label for="first_name" name="{{ __('app.first_name') }}" />
                                        <x-input type="text" name="first_name" :required="true" value="{{ $party->first_name }}"/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="last_name" name="{{ __('app.last_name') }}" />
                                        <x-input type="text" name="last_name" :required="false" value="{{ $party->last_name }}"/>
                                    </div>
                                    
                                    {{-- <div class="col-md-6">
                                        <x-label for="phone" name="{{ __('app.phone') }}" />
                                        <x-input type="number" name="phone" :required="false" value="{{ $party->phone }}"/>
                                    </div> --}}
                                    <div class="col-md-6">
                                        <x-label for="mobile" name="{{ __('app.mobile') }}" />
                                        <x-input type="number" name="mobile" :required="false" value="{{ $party->mobile }}"/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="whatsapp" name="{{ __('app.whatsapp_number') }}" />
                                        <x-input type="number" name="whatsapp" :required="false" value="{{ $party->whatsapp }}"/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="category" name="{{ __('Category') }}" />
                                        <select class="form-select single-select-clear-field" id="category" name="category" data-placeholder="Choose one thing">
                                            <option></option>
                                            @foreach ($categories as $key => $value)
                                                <option value="{{ $value }}" {{ $party->category == $value ? 'selected' : '' }}>{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                   
                                    <div class="col-md-6">
                                        <x-label for="status" name="{{ __('app.status') }}" />
                                        <x-dropdown-status selected="{{ $party->status }}" dropdownName='status'/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="shipping_address" name="{{ __('party.shipping_address') }}" />
                                        <x-textarea name="shipping_address" value="{{ $party->shipping_address }}"/>
                                    </div>
                                    <div class="col-md-4">
                                        <x-label for="credit_limit" name="{{ __('party.credit_limit') }}" />
                                        <div class="input-group mb-3">
                                            <x-dropdown-general optionNaming="creditLimit" selected="{{ $party->is_set_credit_limit }}" dropdownName='is_set_credit_limit'/>
                                            <x-input type="text" additionalClasses="cu_numeric" name="credit_limit" :required="false" value="{{ $formatNumber->formatWithPrecision($party->credit_limit, comma:false) }}"/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <div class="d-md-flex d-grid align-items-center gap-3">
                                            <x-button type="submit" class="primary px-4" text="{{ __('app.submit') }}" />
                                            <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end row-->
            </div>
        </div>
        @endsection

@section('js')
<script src="{{ asset('custom/js/party/party.js') }}"></script>
<script src="{{ asset('custom/js/party/party-edit.js') }}"></script>
<script type="text/javascript">
    var _opening_balance_type = '{{$opening_balance_type}}';
    var _isWholesaleCustomer = '{{$party->is_wholesale_customer}}';
</script>
@endsection
