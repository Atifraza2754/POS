$(function() {
    "use strict";

    let originalButtonText;

    const tableId = $('#dailyReport');

    /**
     * Language
     * */
    const _lang = {
                total : "Total",
                noRecordsFound : "No Records Found!!",
            };

    $("#dailyReportForm").on("submit", function(e) {
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



    // function formAdjustIfSaveOperation(response)
    // {

    //     const tableBody = tableId.find('tbody');
    //     let tr = "";
    //     let id = 1;

    //     let totalAmount = 0;

    //     $.each(response.data, function(index, row) {

    //         const amount = parseFloat(row.amount);
    //         totalAmount += amount;

    //         tr += `
    //             <tr>
    //                 <td>${id++}</td>
    //                 <td>${row.date}</td>
    //                 <td>${row.receipt_no ?? ""}</td>
    //                 <td>${row.payment_type}</td>
    //                 <td>${_formatNumber(amount)}</td>
    //                 <td>${row.user_name ?? "N/A"}</td>
    //             </tr>
    //         `;
    //     });

    //     // Total Row
    //     tr += `
    //         <tr class="fw-bold">
    //             <td colspan="5" class="text-end">${_lang.total}</td>
    //             <td class="text-end">${_formatNumber(totalAmount)}</td>
    //             <td></td>
    //         </tr>
    //     `;

    //     tableBody.empty().append(tr);
    // }



// function formAdjustIfSaveOperation(response) {

//     const tableBody = tableId.find('tbody');
//     let tr = "";
//     let id = 1;

//     const data = response.data;

//     tr += `
//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Total Sale</td>
//             <td>-</td>
//             <td class="text-end">${_formatNumber(data.total_sale)}</td>
//             <td>-</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Amount Received</td>
//             <td>-</td>
//             <td class="text-end">${_formatNumber(data.amount_received)}</td>
//             <td>-</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Total Expense</td>
//             <td>-</td>
//             <td class="text-end">${_formatNumber(data.total_expense)}</td>
//             <td>-</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Total Quantity Sold</td>
//             <td>-</td>
//             <td class="text-end">${data.total_quantity_sold}</td>
//             <td>-</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Purchase Payment Given</td>
//             <td>-</td>
//             <td class="text-end">${_formatNumber(data.purchase_payment)}</td>
//             <td>-</td>
//         </tr>
//     `;

//     tableBody.empty().append(tr);
// }


// function formAdjustIfSaveOperation(response) {

//     const tableBody = tableId.find('tbody');
//     let tr = "";
//     let id = 1;

//     const data = response.data;

//     tr += `
//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Total Sale</td>
//             <td class="text-end">${_formatNumber(data.total_sale)}</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Amount Received</td>
//             <td class="text-end">${_formatNumber(data.amount_received)}</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Total Expense</td>
//             <td class="text-end">${_formatNumber(data.total_expense)}</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Total Quantity Sold</td>
//             <td class="text-end">${data.total_quantity_sold}</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${data.date}</td>
//             <td>Purchase Payment Given</td>
//             <td class="text-end">${_formatNumber(data.purchase_payment)}</td>
//         </tr>
//     `;

//     tableBody.empty().append(tr);
// }

// function formAdjustIfSaveOperation(response) {

//     const tableBody = tableId.find('tbody');
//     let tr = "";
//     let id = 1;

//     const d = response.data;

//     tr += `
//         <tr>
//             <td>${id++}</td>
//             <td>${d.date}</td>
//             <td><strong>Opening Cash</strong></td>
//             <td class="text-end">${_formatNumber(d.opening_cash)}</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${d.date}</td>
//             <td>Total Sale</td>
//             <td class="text-end text-success">+${_formatNumber(d.total_sale)}</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${d.date}</td>
//             <td>Total Recovery</td>
//             <td class="text-end text-success">+${_formatNumber(d.total_recovery)}</td>
//         </tr>
        
        

//         <tr>
//             <td>${id++}</td>
//             <td>${d.date}</td>
//             <td>Total Expense</td>
//             <td class="text-end text-danger">-${_formatNumber(d.total_expense)}</td>
//         </tr>

//         <tr>
//             <td>${id++}</td>
//             <td>${d.date}</td>
//             <td>Suppliers Payment</td>
//             <td class="text-end text-danger">-${_formatNumber(d.supplier_payment)}</td>
//         </tr>

//         <tr class="fw-bold border-top">
//             <td>${id++}</td>
//             <td>${d.date}</td>
//             <td>Closing Total</td>
//             <td class="text-end">${_formatNumber(d.closing_cash)}</td>
//         </tr>

//         <tr class="fw-bold">
//             <td>${id++}</td>
//             <td>${d.date}</td>
//             <td>Total Quantity Sold</td>
//             <td class="text-end">${d.total_quantity_sold}</td>
//         </tr>
//     `;

//     tableBody.empty().append(tr);
// }

let reportDate = "";




function formAdjustIfSaveOperation(response) {

    const tableBody = tableId.find('tbody');
    let tr = "";
    let id = 1;

    const d = response.data;
     reportDate = d.date;

    /* Opening Cash */
    tr += `
        <tr>
            <td>${id++}</td>
            <td>${d.date}</td>
            <td><strong>Opening Cash</strong></td>
            <td class="text-end">${_formatNumber(d.opening_cash)}</td>
        </tr>
    `;

    /* Total Sale */
    tr += `
        <tr>
            <td>${id++}</td>
            <td>${d.date}</td>
            <td>Total Sale</td>
            <td class="text-end text-success">+${_formatNumber(d.total_sale)}</td>
        </tr>
    `;

    /* Total Recovery */
    tr += `
        <tr>
            <td>${id++}</td>
            <td>${d.date}</td>
            <td>Total Recovery</td>
            <td class="text-end text-success">+${_formatNumber(d.total_recovery)}</td>
        </tr>
    `;

    /* 🔹 Recovery by User */
    if (d.recovery_by_user && d.recovery_by_user.length > 0) {
        d.recovery_by_user.forEach(user => {
            tr += `
                <tr class="">
                    <td>${id++}</td>
                    <td>${d.date}</td>
                    <td class="ps-4"> ${user.user_name}</td>
                    <td class="text-end text-success">
                        +${_formatNumber(user.total_collected)}
                    </td>
                </tr>
            `;
        });
    }

    /* Total Expense */
    tr += `
        <tr>
            <td>${id++}</td>
            <td>${d.date}</td>
            <td>Total Expense</td>
            <td class="text-end text-danger">-${_formatNumber(d.total_expense)}</td>
        </tr>
    `;

    /* Supplier Payment */
    tr += `
        <tr>
            <td>${id++}</td>
            <td>${d.date}</td>
            <td>Suppliers Payment</td>
            <td class="text-end text-danger">-${_formatNumber(d.supplier_payment)}</td>
        </tr>
    `;

    /* Closing Cash */
    tr += `
        <tr class="fw-bold border-top">
            <td>${id++}</td>
            <td>${d.date}</td>
            <td>Closing Total</td>
            <td class="text-end">${_formatNumber(d.closing_cash)}</td>
        </tr>
    `;

    /* Quantity Sold */
    tr += `
        <tr class="fw-bold">
            <td>${id++}</td>
            <td>${d.date}</td>
            <td>Total Quantity Sold</td>
            <td class="text-end">${d.total_quantity_sold}</td>
        </tr>
    `;

    tableBody.empty().append(tr);
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


$(document).on("click", '#generate_pdf', function () {

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // 🔹 Company Title
    doc.setFontSize(18);
    doc.text("Shahid Traders", 105, 15, { align: "center" });

    // 🔹 Report Info
    doc.setFontSize(11);
    doc.text("Report Name: Daily Report", 14, 25);
    // doc.text("Date: 12/2/2025 - 12/2/2025", 14, 32);
      doc.text(`Date: ${reportDate}`, 14, 32);

    // 🔹 Table Export
    doc.autoTable({
        html: '#dailyReport',
        startY: 40,
        theme: 'grid',
        headStyles: { fillColor: [41, 128, 185] },
        styles: { fontSize: 9 }
    });

    doc.save("daily_report.pdf");
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
