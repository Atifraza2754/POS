$(function() {
"use strict";

let originalButtonText;
let profitResponse = null;

const tableId = $('#reportTable');

const _lang = {
    total : "Total",
    noRecordsFound : "No Records Found!!",
};

$("#reportForm").on("submit", function(e) {
    e.preventDefault();

    const form = $(this);

    const formArray = {
        formId: form.attr("id"),
        csrf: form.find('input[name="_token"]').val(),
        url: form.closest('form').attr('action'),
        formObject : form,
    };

    ajaxRequest(formArray);
});

function disableSubmitButton(form) {

    originalButtonText = form.find('button[type="submit"]').text();

    form.find('button[type="submit"]')
        .prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm"></span> Loading...');
}

function enableSubmitButton(form) {

    form.find('button[type="submit"]')
        .prop('disabled', false)
        .html(originalButtonText);
}

function beforeCallAjaxRequest(formObject){

    disableSubmitButton(formObject);
    showSpinner();
}

function afterCallAjaxResponse(formObject){

    enableSubmitButton(formObject);
    hideSpinner();
}

function afterSeccessOfAjaxRequest(formObject, response){

    profitResponse = response;

    formAdjustIfSaveOperation(response);
}

function afterFailOfAjaxRequest(formObject){

    showNoRecordsMessageOnTableBody();
}

function ajaxRequest(formArray){

    var formData = new FormData(document.getElementById(formArray.formId));

    var jqxhr = $.ajax({
        type: 'POST',
        url: formArray.url,
        data: formData,
        dataType: 'json',
        contentType: false,
        processData: false,
        headers: {
            'X-CSRF-TOKEN': formArray.csrf
        },

        beforeSend: function() {

            if (typeof beforeCallAjaxRequest === 'function') {

                beforeCallAjaxRequest(formArray.formObject);
            }
        },
    });

    jqxhr.done(function(response) {

        if (typeof afterSeccessOfAjaxRequest === 'function') {

            afterSeccessOfAjaxRequest(formArray.formObject, response);
        }
    });

    jqxhr.fail(function(response) {

        var message = response.responseJSON.message;

        iziToast.error({
            title: 'Error',
            layout: 2,
            message: message
        });

        if (typeof afterFailOfAjaxRequest === 'function') {

            afterFailOfAjaxRequest(formArray.formObject);
        }
    });

    jqxhr.always(function() {

        if (typeof afterCallAjaxResponse === 'function') {

            afterCallAjaxResponse(formArray.formObject);
        }
    });
}


function formAdjustIfSaveOperation(response){

    let _values = response.data;

    $("#sale_total").text(_formatNumber(_values.sale_total ?? 0));
    $("#expense_total").text(_formatNumber(_values.expense_total ?? 0));
    $("#profit_total").text(_formatNumber(_values.profit_total ?? 0));
    $("#net_profit").text(_formatNumber(_values.net_profit ?? 0));
}



function showNoRecordsMessageOnTableBody() {

}


function columnCountWithoutDNoneClass(minusCount) {

}



/*
=====================================
EXPORT EXCEL
=====================================
*/

$(document).on("click", '#generate_excel', function() {

    tableId.tableExport({

        formats: ["xlsx"],

        xlsx: {

            onCellFormat: function (cell, e) {

                if (typeof e.value === 'string') {

                    var numValue = parseFloat(e.value.replace(/,/g, ''));

                    if (!isNaN(numValue)) {

                        return numValue;
                    }
                }

                return e.value;
            }
        }
    });
});


/*
=====================================
PROFIT PDF EXPORT (Styled)
=====================================
*/

$(document).on("click", "#generate_pdf", function () {

    if (!profitResponse || !profitResponse.data) {

        alert("No profit data to export");

        return;
    }

    const { jsPDF } = window.jspdf;

    const doc = new jsPDF('p','mm','a4');

    let startY = 15;

    const pageWidth = doc.internal.pageSize.getWidth();


    // COMPANY NAME
    doc.setFontSize(18);
    doc.setFont("helvetica","bold");

    doc.text("Shahid Traders", pageWidth / 2, startY, {align:"center"});

    startY += 8;


    // REPORT TITLE
    doc.setFontSize(12);
    doc.setFont("helvetica","normal");

    doc.text("Profit Report", pageWidth / 2, startY, {align:"center"});

    startY += 10;


    // PERIOD
    doc.setFontSize(10);

    const fromDate = $("input[name='from_date']").val();
    const toDate   = $("input[name='to_date']").val();

    doc.text(`Period: ${fromDate} - ${toDate}`,14,startY);

    startY += 10;


    const tableBody = [];

    function parseAmount(val){
        const v = (val === null || val === undefined) ? 0 : String(val).replace(/,/g, '');
        const n = parseFloat(v);
        return isNaN(n) ? '0.00' : n.toFixed(2);
    }

    tableBody.push([
        "1",
        "Sale",
        parseAmount(profitResponse.data.sale_total)
    ]);

    tableBody.push([
        "2",
        "Expense",
        parseAmount(profitResponse.data.expense_total)
    ]);

    tableBody.push([
        "3",
        "Profit",
        parseAmount(profitResponse.data.profit_total)
    ]);

    tableBody.push([
        "4",
        "Net Profit",
        parseAmount(profitResponse.data.net_profit)
    ]);


    doc.autoTable({

        startY:startY,

        head:[[
            '#',
            'Title',
            'Amount'
        ]],

        body:tableBody,

        theme:'grid',

        styles:{
            fontSize:9,
            cellPadding:3
        },

        headStyles:{
            fillColor:[41,128,185],
            textColor:255,
            fontStyle:'bold'
        },

        columnStyles:{
            2:{halign:'right'}
        }

    });


    doc.save("profit_report.pdf");

});


});