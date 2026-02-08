$(function() {
    "use strict";

    let originalButtonText;

    const tableId = $('#duePaymentReport');

    /**
     * Language
     * */
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
    let dueResponse = null;

    function afterSeccessOfAjaxRequest(formObject, response){
        formAdjustIfSaveOperation(response);
        dueResponse = response; // store for PDF export
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

    function formAdjustIfSaveOperation(response){
        var tableBody = tableId.find('tbody');

        var id = 1;
        var tr = "";
        
        var totalAmount = parseFloat(0);
        
        $.each(response.data, function(index, party) {
            totalAmount += parseFloat(party.due_amount);

            tr  +=`
                <tr>
                    <td>${id++}</td>
                    <td>${party.party_name}</td>
                    <td class='text-end ${party.className}' data-tableexport-celltype="number" >${_formatNumber(party.due_amount)}</td>
                    <td class='${party.className}'>${party.status }</td>
                </tr>
            `;
        });

        tr  +=`
            <tr class='fw-bold'>
                <td colspan='0' class='text-end tfoot-first-td'>${_lang.total}</td>
                <td class='text-end' data-tableexport-celltype="number">${_formatNumber(totalAmount)}</td>
            </tr>
        `;

        // Clear existing rows:
        tableBody.empty();
        tableBody.append(tr);

        /**
         * Set colspan of the table bottom
         * */
        $('.tfoot-first-td').attr('colspan', columnCountWithoutDNoneClass(1)-1);
    }

    function showNoRecordsMessageOnTableBody() {
        var tableBody = tableId.find('tbody');

        var tr = "<tr class='fw-bold'>";
        tr += `<td colspan='0' class='text-end tfoot-first-td text-center'>${_lang.noRecordsFound}</td>"`;
        tr += "</tr>";

        tableBody.empty();
        tableBody.append(tr);

        /**
         * Set colspan of the table bottom
         * */
        $('.tfoot-first-td').attr('colspan', columnCountWithoutDNoneClass(0));
    }
    function columnCountWithoutDNoneClass(minusCount) {
        return tableId.find('thead > tr:first > th').not('.d-none').length - minusCount;
    }

    /** 
     * 
     * Table Exporter
     * PDF, SpreadSheet
     * */
    $(document).on("click", '#generate_pdf', function() {
        if (!dueResponse || !dueResponse.data.length) {
            alert("No data to export");
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');

        let startY = 15;
        const pageWidth = doc.internal.pageSize.getWidth();

        // COMPANY NAME
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Shahid Traders", pageWidth / 2, startY, { align: "center" });

        startY += 8;

        // REPORT TITLE
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        doc.text("Supplier Due Payment Report", pageWidth / 2, startY, { align: "center" });

        startY += 10;

        // SUPPLIER INFO
        const selectedSupplier = $('#party_id').select2 ? ($('#party_id').select2('data')[0] ? $('#party_id').select2('data')[0].text : '') : $('#party_id option:selected').text();

        doc.setFontSize(10);

        if ($('#party_id').val() && selectedSupplier) {
            doc.text(`Supplier: ${selectedSupplier}`, 14, startY);
            startY += 6;
        }

        startY += 8;

        // TABLE DATA (always print per-supplier rows to match filtered table)
        const tableBody = [];
        let totalAmount = 0;

        let counter = 1;
        dueResponse.data.forEach(function(item) {
            tableBody.push([
                counter++,
                item.party_name,
                parseFloat(item.due_amount).toFixed(2)
            ]);
            totalAmount += parseFloat(item.due_amount);
        });

        // Totals row
        tableBody.push([
            '',
            'TOTAL',
            totalAmount.toFixed(2)
        ]);

        doc.autoTable({
            startY: startY,
            head: [[
                '#',
                'Supplier',
                'Due Payment'
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
                2: { halign: 'right' }
            }
        });

        doc.save("supplier_due_payment_report.pdf");
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
