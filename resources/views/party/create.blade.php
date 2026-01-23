@extends('layouts.app')
@section('title', $lang['party_create'])

		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
				<x-breadcrumb :langArray="[
											'party.contacts',
											$lang['party_list'],
											$lang['party_create'],
										]"/>
				<div class="row">
					<div class="col-12 col-lg-12">
                        <div class="card">
                            <div class="card-header px-4 py-3">
                                <h5 class="mb-0">{{ $lang['party_details'] }}</h5>
                            </div>
                            <div class="card-body p-4">
                                <form class="row g-3 needs-validation" id="partyForm" action="{{ route('party.store') }}" enctype="multipart/form-data">
                                    {{-- CSRF Protection --}}
                                    @csrf
                                    @method('POST')

                                    <input type="hidden" name="party_type" value="{{ $lang['party_type'] }}">
                                    <input type="hidden" id="operation" name="operation" value="save">
                                    <input type="hidden" id="base_url" value="{{ url('/') }}">

                                  
                                    <div class="col-md-6">
                                        <x-label for="first_name" name="{{ __('app.first_name') }}" />
                                        <x-input type="text" name="first_name" :required="true" value=""/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="last_name" name="{{ __('app.last_name') }}" />
                                        <x-input type="text" name="last_name" :required="false" value=""/>
                                    </div>
                                    {{-- <div class="col-md-6">
                                        <x-label for="email" name="{{ __('app.email') }}" />
                                        <x-input type="email" name="email" :required="false" value=""/>
                                    </div> --}}
                                    {{-- <div class="col-md-6">
                                        <x-label for="phone" name="{{ __('app.phone') }}" />
                                        <x-input type="number" name="phone" :required="false" value=""/>
                                    </div> --}}
                                    <div class="col-md-6">
                                        <x-label for="mobile" name="{{ __('app.mobile') }}" />
                                        <x-input type="number" name="mobile" :required="false" value=""/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="whatsapp" name="{{ __('app.whatsapp_number') }}" />
                                        <x-input type="number" name="whatsapp" :required="false" value=""/>
                                    </div>
                                    @if ( $lang['party_type']  == 'customer')
                                        <div class="col-md-6">
                                        <x-label for="category" name="{{ __('Category') }}" />
                                        <select class="form-select single-select-clear-field" id="category" name="category" data-placeholder="Choose one thing">
                                            <option></option>
                                            @foreach ($categories as $key => $value)
                                                <option value="{{ $value }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>    
                                    @endif
                                    
                                    

                                    <div class="col-md-6">
                                        <x-label for="status" name="{{ __('app.status') }}" />
                                        <x-dropdown-status selected="" dropdownName='status'/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="shipping_address" name="{{ __('party.shipping_address') }}" />
                                        <x-textarea name="shipping_address" value=""/>
                                    </div>
                                     <div class="col-md-6">
                                        <x-label for="credit_limit" name="{{ __('party.credit_limit') }}" />
                                        <div class="input-group mb-3">
                                            <x-dropdown-general optionNaming="creditLimit" selected="" dropdownName='is_set_credit_limit'/>
                                            <x-input type="text" additionalClasses="cu_numeric" name="credit_limit" :required="false" value="0"/>
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
@endsection
