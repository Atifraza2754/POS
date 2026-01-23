$(function() {
    "use strict";

    let originalButtonText;

    const tableId = $('#cashinReport');

    /**
     * Language
     * */
    const _lang = {
                total : "Total",
                noRecordsFound : "No Records Found!!",
            };

    $("#cashinReportForm").on("submit", function(e) {
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
            .html('  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...');
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
        formAdjustIfSaveOperation(response);
         cashInResponse = response;

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
                // Actions to be performed before sending the AJAX request
                if (typeof beforeCallAjaxRequest === 'function') {
                    beforeCallAjaxRequest(formArray.formObject);
                }
            },
        });
        jqxhr.done(function(response) {
            // Actions to be performed after response from the AJAX request
            if (typeof afterSeccessOfAjaxRequest === 'function') {
                afterSeccessOfAjaxRequest(formArray.formObject, response);
            }
        });
        jqxhr.fail(function(response) {
            var message = response.responseJSON.message;
            iziToast.error({title: 'Error', layout: 2, message: message});
            if (typeof afterFailOfAjaxRequest === 'function') {
                afterFailOfAjaxRequest(formArray.formObject);
            }
        });
        jqxhr.always(function() {
            // Actions to be performed after the AJAX request is completed, regardless of success or failure
            if (typeof afterCallAjaxResponse === 'function') {
                afterCallAjaxResponse(formArray.formObject);
            }
        });
    }



function formAdjustIfSaveOperation(response) {

    const tableBody = tableId.find('tbody');
    let tr = "";
    let id = 1;

    let totalAmount = 0;

    $.each(response.data, function(index, row) {

        const amount = parseFloat(row.amount);
        totalAmount += amount;

        tr += `
            <tr>
                <td>${id++}</td>
                <td>${row.date}</td>
                <td>${row.receipt_no ?? ""}</td>
                <td>${row.payment_type}</td>
                <td>${_formatNumber(amount)}</td>
                <td>${row.user_name ?? "N/A"}</td>
            </tr>
        `;
    });

    // Total Row
    tr += `
        <tr class="fw-bold">
            <td colspan="5" class="text-end">${_lang.total}</td>
            <td class="text-end">${_formatNumber(totalAmount)}</td>
            <td></td>
        </tr>
    `;

    tableBody.empty().append(tr);
}





    // function showNoRecordsMessageOnTableBody() {
    //     var tableBody = tableId.find('tbody');

    //     var tr = "<tr class='fw-bold'>";
    //     tr += `<td colspan='0' class='text-end tfoot-first-td text-center'>${_lang.noRecordsFound}</td>"`;
    //     tr += "</tr>";

    //     tableBody.empty();
    //     tableBody.append(tr);

    //     /**
    //      * Set colspan of the table bottom
    //      * */
    //     $('.tfoot-first-td').attr('colspan', columnCountWithoutDNoneClass(0));
    // }
    function showNoRecordsMessageOnTableBody() {
    const tableBody = tableId.find('tbody');

    const tr = `
        <tr class='fw-bold'>
            <td colspan='3' class='text-center'>${_lang.noRecordsFound}</td>
        </tr>
    `;

    tableBody.empty().append(tr);
}

    function columnCountWithoutDNoneClass(minusCount) {
        
        return tableId.find('thead > tr:first > th').not('.d-none').length - minusCount;
    }

    /** 
     * 
     * Table Exporter
     * PDF, SpreadSheet
     * */
    // $(document).on("click", '#generate_pdf', function() {
    //     tableId.tableExport({type:'pdf',escape:'false'});
    // });


//     let cashInResponse = null;

// $(document).on("click", "#generate_pdf", function () {

//     if (!cashInResponse || !cashInResponse.data.length) {
//         alert("No cash-in data to export");
//         return;
//     }

//     const { jsPDF } = window.jspdf;
//     const doc = new jsPDF('p', 'mm', 'a4');

//     let startY = 15;
//     const pageWidth = doc.internal.pageSize.getWidth();

//     // ------------------------------------
//     // COMPANY NAME
//     // ------------------------------------
//     doc.setFontSize(18);
//     doc.setFont("helvetica", "bold");
//     doc.text("Shahid Traders", pageWidth / 2, startY, { align: "center" });

//     startY += 8;

//     // ------------------------------------
//     // REPORT TITLE
//     // ------------------------------------
//     doc.setFontSize(12);
//     doc.setFont("helvetica", "normal");
//     doc.text("Cash-In Report", pageWidth / 2, startY, { align: "center" });

//     startY += 10;

//     // ------------------------------------
//     // CUSTOMER / USER INFO
//     // ------------------------------------
//     const customer = ledgerResponse.customer;
//     const user     = ledgerResponse.user;

//     doc.setFontSize(10);

//     const customerName = [
//     customer.first_name,
//     customer.last_name
// ].filter(Boolean).join(' ');

// doc.text(`Customer: ${customerName}`, 14, startY);
//     startY += 6;

    

//     if (customer.phone) {
//         doc.text(`Phone: ${customer.phone}`, 14, startY);
//         startY += 6;
//     }

//     if (customer.address) {
//         doc.text(`Address: ${customer.address}`, 14, startY);
//         startY += 6;
//     }

//     if (user) {
//         doc.text(`User: ${user.username}`, 14, startY);
//         startY += 6;
//     }

//     doc.text(
//     `Period: ${ledgerResponse.from_date} - ${ledgerResponse.to_date}`,
//     14,
//     startY
// );

//     startY += 10;

//     // ------------------------------------
//     // TABLE DATA (FROM JSON)
//     // ------------------------------------
//     const tableBody = [];

//     ledgerResponse.data.forEach(row => {
//         tableBody.push([
//             row.date,
//             row.description,
//             row.debit ? row.debit.toFixed(2) : '—',
//             row.credit ? row.credit.toFixed(2) : '—',
//             row.balance.toFixed(2)
//         ]);
//     });

//     // Totals row
//     tableBody.push([
//         '',
//         'TOTAL',
//         ledgerResponse.total_debit.toFixed(2),
//         ledgerResponse.total_credit.toFixed(2),
//         ledgerResponse.closing_balance.toFixed(2)
//     ]);

//     // ------------------------------------
//     // AUTO TABLE
//     // ------------------------------------
//     doc.autoTable({
//         startY: startY,
//         head: [[
//             'Date',
//             'Description',
//             'Debit',
//             'Credit',
//             'Balance'
//         ]],
//         body: tableBody,
//         theme: 'grid',
//         styles: {
//             fontSize: 9,
//             cellPadding: 3
//         },
//         headStyles: {
//             fillColor: [41, 128, 185],
//             textColor: 255,
//             fontStyle: 'bold'
//         },
//         columnStyles: {
//             2: { halign: 'right' },
//             3: { halign: 'right' },
//             4: { halign: 'right' }
//         }
//     });

//     // ------------------------------------
//     // SAVE
//     // ------------------------------------
//     doc.save("cashin_report.pdf");
// });

let cashInResponse = null;

$(document).on("click", "#generate_pdf", function () {

    if (!cashInResponse || !cashInResponse.data.length) {
        alert("No cash-in data to export");
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');

    let startY = 15;
    const pageWidth = doc.internal.pageSize.getWidth();

    // ------------------------------------
    // COMPANY NAME
    // ------------------------------------
    doc.setFontSize(18);
    doc.setFont("helvetica", "bold");
    doc.text("Shahid Traders", pageWidth / 2, startY, { align: "center" });

    startY += 8;

    // ------------------------------------
    // REPORT TITLE
    // ------------------------------------
    doc.setFontSize(12);
    doc.setFont("helvetica", "normal");
    doc.text("Cash-In Report", pageWidth / 2, startY, { align: "center" });

    startY += 10;

    // ------------------------------------
    // USER & DATE INFO
    // ------------------------------------
    doc.setFontSize(10);

   if (cashInResponse.user_name) {
    doc.text(`User: ${cashInResponse.user_name}`, 14, startY);
    startY += 6;
    }

    doc.text(
        `Period: ${cashInResponse.from_date} - ${cashInResponse.to_date}`,
        14,
        startY
    );

    startY += 10;

    // ------------------------------------
    // TABLE DATA
    // ------------------------------------
    const tableBody = [];
    let totalAmount = 0;

    cashInResponse.data.forEach(row => {
        totalAmount += row.amount;

        tableBody.push([
            row.date,
            row.receipt_no,
            row.payment_type,
            row.user_name,
            row.amount.toFixed(2)
        ]);
    });

    // TOTAL ROW
    tableBody.push([
        '',
        '',
        '',
        'TOTAL',
        totalAmount.toFixed(2)
    ]);

    // ------------------------------------
    // AUTO TABLE
    // ------------------------------------
    doc.autoTable({
        startY: startY,
        head: [[
            'Date',
            'Receipt #',
            'Payment Type',
            'User',
            'Amount'
        ]],
        body: tableBody,
        theme: 'grid',
        styles: {
            fontSize: 9,
            cellPadding: 3
        },
        headStyles: {
            fillColor: [41, 128, 185],
            textColor: 255,
            fontStyle: 'bold'
        },
        columnStyles: {
            4: { halign: 'right' }
        }
    });

    // ------------------------------------
    // SAVE PDF
    // ------------------------------------
    doc.save("cashin_report.pdf");
});


    $(document).on("click", '#generate_excel', function() {
        tableId.tableExport({
            formats: ["xlsx"],
            xlsx: {
                onCellFormat: function (cell, e) {
                    if (typeof e.value === 'string') {
                        // Remove commas and convert to number
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
    
});//main function
