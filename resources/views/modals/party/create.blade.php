<!-- Tax Modal: start -->
<div class="modal fade" id="partyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" >Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="row g-3 p-3 needs-validation" id="partyForm" action="{{ route('party.store') }}" enctype="multipart/form-data">
                                    {{-- CSRF Protection --}}
                                    @csrf
                                    @method('POST')

                                    <input type="hidden" name="party_type" value="customer">
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
                                    <div class="col-md-6">
                                        <x-label for="category" name="{{ __('Category') }}" />
                                        <select class="form-select single-select-clear-field" id="category" name="category" data-placeholder="Choose one thing">
                                            <option></option>
                                            @foreach ($categories as $key => $value)
                                                <option value="{{ $value }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    

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
<!-- Tax Modal: end -->
