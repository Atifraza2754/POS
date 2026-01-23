$(function() {
    "use strict";

    let originalButtonText;

    const tableId = $('#customerLedgerReport');

    /**
     * Language
     * */
    const _lang = {
                total : "Total",
                noRecordsFound : "No Records Found!!",
            };

    $("#customerLedgerReportForm").on("submit", function(e) {
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
         ledgerResponse = response;
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



// function formAdjustIfSaveOperation(response) {
//     const tableBody = tableId.find('tbody');
//     let id = 1;
//     let tr = "";

//     let totalSale = 0;
//     let totalPaid = 0;
//     let totalBalance = 0;

//     $.each(response.data, function(index, row) {
//         const customer = row.customer_name ?? "N/A";
//         const sale = parseFloat(row.total_sale) || 0;
//         const paid = parseFloat(row.total_paid) || 0;
//         const balance = parseFloat(row.remaining) || 0;

//         totalSale += sale;
//         totalPaid += paid;
//         totalBalance += balance;

//         tr += `
//             <tr>
//                 <td>${id++}</td>
//                 <td>${customer}</td>
//                 <td class="text-end">${_formatNumber(sale)}</td>
//                 <td class="text-end">${_formatNumber(paid)}</td>
//                 <td class="text-end">${_formatNumber(balance)}</td>
//             </tr>
//         `;
//     });

//     // totals row
//     tr += `
//         <tr class="fw-bold">
//             <td colspan="2" class="text-end">${_lang.total}</td>
//             <td class="text-end">${_formatNumber(totalSale)}</td>
//             <td class="text-end">${_formatNumber(totalPaid)}</td>
//             <td class="text-end">${_formatNumber(totalBalance)}</td>
//         </tr>
//     `;

//     tableBody.empty().append(tr);
// }





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


// function formAdjustIfSaveOperation(response) {
//     const tableBody = tableId.find('tbody');
//     let tr = "";

//     $.each(response.data, function(i, row) {

//         const debit = row.debit ? _formatNumber(row.debit) : "—";
//         const credit = row.credit ? _formatNumber(row.credit) : "—";

//         tr += `
//             <tr>
//                 <td>${row.date}</td>
//                 <td>${row.description}</td>
//                 <td class="text-end">${debit}</td>
//                 <td class="text-end">${credit}</td>
//                 <td class="text-end">${_formatNumber(row.balance)}</td>
//             </tr>
//         `;
//     });
//     tr += `
//     <tr class="fw-bold bg-light">
//         <td colspan="2" class="text-end">Totals</td>
//         <td class="text-end">${_formatNumber(response.total_debit)}</td>
//         <td class="text-end">${_formatNumber(response.total_credit)}</td>
//         <td class="text-end">${_formatNumber(response.total_balance)}</td>
//     </tr>
// `;


//     tableBody.html(tr);
// }


function formAdjustIfSaveOperation(response) {
    const tableBody = tableId.find('tbody');
    let tr = "";

    $.each(response.data, function (i, row) {

        const debit  = row.debit  > 0 ? _formatNumber(row.debit)  : "—";
        const credit = row.credit > 0 ? _formatNumber(row.credit) : "—";

        tr += `
            <tr>
                <td>${row.date}</td>
                <td>${row.description}</td>
                <td class="text-end">${debit}</td>
                <td class="text-end">${credit}</td>
                <td class="text-end">${_formatNumber(row.balance)}</td>
            </tr>
        `;
    });

    tr += `
        <tr class="fw-bold bg-light">
            <td colspan="2" class="text-end">Totals</td>
            <td class="text-end">${_formatNumber(response.total_debit)}</td>
            <td class="text-end">${_formatNumber(response.total_credit)}</td>
            <td class="text-end">${_formatNumber(response.closing_balance)}</td>
        </tr>
    `;

    tableBody.html(tr);
}




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

let ledgerResponse = null;

    $(document).on("click", "#generate_pdf", function () {

    if (!ledgerResponse || !ledgerResponse.data.length) {
        alert("No ledger data to export");
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
    doc.text("Customer Ledger Report", pageWidth / 2, startY, { align: "center" });

    startY += 10;

    // ------------------------------------
    // CUSTOMER / USER INFO
    // ------------------------------------
    const customer = ledgerResponse.customer;
    const user     = ledgerResponse.user;

    doc.setFontSize(10);

    const customerName = [
    customer.first_name,
    customer.last_name
].filter(Boolean).join(' ');

doc.text(`Customer: ${customerName}`, 14, startY);
    startY += 6;

    

    if (customer.phone) {
        doc.text(`Phone: ${customer.phone}`, 14, startY);
        startY += 6;
    }

    if (customer.address) {
        doc.text(`Address: ${customer.address}`, 14, startY);
        startY += 6;
    }

    if (user) {
        doc.text(`User: ${user.username}`, 14, startY);
        startY += 6;
    }

    doc.text(
    `Period: ${ledgerResponse.from_date} - ${ledgerResponse.to_date}`,
    14,
    startY
);

    startY += 10;

    // ------------------------------------
    // TABLE DATA (FROM JSON)
    // ------------------------------------
    const tableBody = [];

    ledgerResponse.data.forEach(row => {
        tableBody.push([
            row.date,
            row.description,
            row.debit ? row.debit.toFixed(2) : '—',
            row.credit ? row.credit.toFixed(2) : '—',
            row.balance.toFixed(2)
        ]);
    });

    // Totals row
    tableBody.push([
        '',
        'TOTAL',
        ledgerResponse.total_debit.toFixed(2),
        ledgerResponse.total_credit.toFixed(2),
        ledgerResponse.closing_balance.toFixed(2)
    ]);

    // ------------------------------------
    // AUTO TABLE
    // ------------------------------------
    doc.autoTable({
        startY: startY,
        head: [[
            'Date',
            'Description',
            'Debit',
            'Credit',
            'Balance'
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
            2: { halign: 'right' },
            3: { halign: 'right' },
            4: { halign: 'right' }
        }
    });

    // ------------------------------------
    // SAVE
    // ------------------------------------
    doc.save("customer_ledger_report.pdf");
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
