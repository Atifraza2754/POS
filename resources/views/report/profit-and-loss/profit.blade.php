@extends('layouts.app')
@section('title', 'Profit Report')

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'app.reports',
                                            'Profit',
                                        ]"/>
                <div class="row">
                    <form class="row g-3 needs-validation" id="reportForm" action="{{ route('report.profit_and_loss.ajax') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf
                        @method('POST')

                        <input type="hidden" name="row_count" value="0">
                        <input type="hidden" name="total_amount" value="0">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">
                        <div class="col-12 col-lg-12">
                            <div class="card">
                                <div class="card-header px-4 py-3">
                                    <h5 class="mb-0">Profit Report</h5>
                                </div>
                                <div class="card-body p-4 row g-3">
                                    
                                        <div class="col-md-6">
                                            <x-label for="from_date" name="{{ __('app.from_date') }}" />
                                            <div class="input-group mb-3">
                                                <x-input type="text" additionalClasses="datepicker-month-first-date" name="from_date" :required="true" value=""/>
                                                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <x-label for="to_date" name="{{ __('app.to_date') }}" />
                                            <div class="input-group mb-3">
                                                <x-input type="text" additionalClasses="datepicker" name="to_date" :required="true" value=""/>
                                                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                            </div>
                                        </div>
                                        
                                </div>

                                <div class="card-body p-4 row g-3">
                                        <div class="col-md-12">
                                            <div class="d-md-flex d-grid align-items-center gap-3">
                                                <x-button type="submit" class="primary px-4" text="{{ __('app.submit') }}" />
                                                <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-12">
                            <div class="card">
                                <div class="card-header px-4 py-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="mb-0">Profit Report</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-4 row g-3">
                                        <div class="col-md-12">
                                            <div class="row mb-2">
                                                <div class="col-6">
                                                    <!-- left empty for alignment -->
                                                </div>
                                                <div class="col-6 text-end">
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-outline-success">{{ __('app.export') }}</button>
                                                        <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <span class="visually-hidden">Toggle Dropdown</span>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><button type='button' class="dropdown-item" id="generate_excel"><i class="bx bx-spreadsheet mr-1"></i>{{ __('app.excel') }}</button></li>
                                                            <li><button type='button' class="dropdown-item" id="generate_pdf"><i class="bx bx-file mr-1"></i>{{ __('app.export_to_pdf') }}</button></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="printForm">
                                                <div class="table-responsive">
                                                    <table id="reportTable" class="table table-bordered table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th class="text-center">#S.No</th>
                                                                <th class="text-start">Title</th>
                                                                <th class="text-end">Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td class="text-center">1</td>
                                                                <td>Sale</td>
                                                                <td id="sale_total" class='text-end' data-tableexport-celltype="number">0.00</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-center">2</td>
                                                                <td>Expense</td>
                                                                <td id="expense_total" class='text-end' data-tableexport-celltype="number">0.00</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-center">3</td>
                                                                <td>Profit</td>
                                                                <td id="profit_total" class='text-end' data-tableexport-celltype="number">0.00</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-center">4</td>
                                                                <td>Net Profit</td>
                                                                <td id="net_profit" class='text-end' data-tableexport-celltype="number">0.00</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!--end row-->
            </div>
        </div>
        <!-- Import Modals -->

        @endsection

@section('js')

@include("plugin.export-table")

<script src="{{ asset('custom/js/reports/profit-and-loss/profit.js') }}"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

@endsection
