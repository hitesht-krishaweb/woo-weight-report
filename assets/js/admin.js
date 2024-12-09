document.addEventListener('DOMContentLoaded', function () {
    
    const elements = document.querySelectorAll('.toggle-view');
    const viewAll = document.querySelectorAll('.toggle-view-all');

    if( 'woocommerce_page_woo-weight-report' === pagenow ){
        const selectElement = document.getElementById('filter-month');
        const startDate = document.getElementById('start_date_weight');
        const endDate = document.getElementById('end_date_weight');
        const weightBtn = document.getElementById('weight-filter');
        const filterMonth = document.getElementById('filter-by-date');
        const paiddate = document.querySelectorAll('.paiddate_data');

        // Set max date to today's date
        const today = new Date().toISOString().split('T')[0]; // Format as YYYY-MM-DD
        startDate.max = today;
        endDate.max = today;   

        // Month disable on load
        if(filterMonth){
            filterMonth.style.display = 'none';     
        }

        const toggleElements = (showCustomRange) => {
            weightBtn.disabled = showCustomRange;
            startDate.style.display = showCustomRange ? 'inline-block' : 'none';
            endDate.style.display = showCustomRange ? 'inline-block' : 'none';
            if(filterMonth){
                filterMonth.style.display = showCustomRange ? 'none' : 'inline-block';
            }
            if (showCustomRange ) {
                filterMonth.selectedIndex = 0;
            }
            startDate.value = '';
            endDate.value = '';
        };

        const checkInput = () => {
            if (startDate.value && endDate.value) {
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);

                if (start <= end) {
                    weightBtn.disabled = false;  // Enable button if dates are valid.
                    endDate.setCustomValidity('');  // Clear any custom validation messages.
                } else {
                    weightBtn.disabled = true;
                    endDate.setCustomValidity('End date cannot be earlier than start date');
                }
            } else {
                weightBtn.disabled = true;
            }
        };

        startDate?.addEventListener('change', checkInput);
        endDate?.addEventListener('change', checkInput);

        selectElement?.addEventListener('change', function () {
            const selectedValue = selectElement.value;
            if (selectedValue === 'custom_range') {
                toggleElements(true);
            } else if (selectedValue === 'custom_month') {
                toggleElements(false);
            } else {
                toggleElements(false);
                filterMonth.style.display = 'none';
            }
        });

        if( 'custom_month' === selectElement.value && filterMonth ){
            filterMonth.style.display = 'inline-block';
        }

        // Attach click event listener to all date elements.
        paiddate.forEach(dateElement => {
            dateElement.addEventListener('click', async (e) => {
                const id = e.target.getAttribute('data-id');
                const date = e.target.getAttribute('data-date');
                const today = new Date();
                const minDate = today.toISOString().split('T')[0];
                const minTime = today.toLocaleTimeString();
                const targetElement = e.target;
                // SweetAlert for date selection
                const value = await swal({
                    title: "Update Paid Date",
                    text: "Please select a new date and time.",
                    content: {
                        element: "input",
                        attributes: {
                            type: "datetime-local",
                            value: date,
                            id: "datetimeInput",
                            max: `${minDate}T${minTime}`
                        }
                    },
                    buttons: true,
                    dangerMode: false,
                });
            
                // Proceed if the value is valid.
                if (value) {
                    const updatedDateTime = document.getElementById('datetimeInput').value;
            
                    // Validate if the input is correct
                    if (!updatedDateTime) {
                        swal("Error", "Please select a valid date and time.", "error");
                        return;
                    }

                    const updatedDate = new Date(updatedDateTime);
                    const formattedTime = updatedDate.toLocaleTimeString();
                    const formattedDate = updatedDate.toISOString().split('T')[0];

                    try {
                        // Send the AJAX request using fetch.
                        const response = await fetch(ajaxurl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: new URLSearchParams({
                                action: 'change_order_paiddate',
                                orderid: id,
                                new_time: formattedTime,
                                new_date: formattedDate,
                                _ajax_nonce: changedate.nonce
                            })
                        });
            
                        const data = await response.json();
                        // Check if update was successfull.
                        if (data.success && ! data.data.verify_status) {
                            targetElement.setAttribute('data-date', data.data.datetime_string);
                            targetElement.innerHTML = data.data.datetime_string;
                            swal({
                                title: "Success!",
                                text: "The date was updated successfully.",
                                icon: "success",
                                buttons: false,
                                timer: 1000
                            }).then(() => {
                                // Refresh the current page.
                                window.location.reload();
                            });
                        } else if( ! data.success && data.data.verify_status ) {
                            swal("Error", data.data.message, "error");
                        } else {
                            swal("Error", "Failed to update the date.", "error");
                        }
                    } catch (error) {
                        console.error('AJAX request failed:', error);
                        swal("Error", "There was a problem with the request.", "error");
                    }
                }
            });
        });
    }
    
    // Loop through each element and toggle the 'hidden' class.
    elements.forEach(element => {
        element.addEventListener('click', (e) => {
            const id = e.target.getAttribute( 'data-id' );
            // Toggle the button text between 'View' and 'Hide'.
            e.target.innerHTML = e.target.innerHTML === 'View' ? 'Hide' : 'View';
            // Toggle the visibility of the associated table.
            document.getElementById(`table-view-${id}`).classList.toggle('hidden');
        });
    });

    // Get the table rows and the viewAll button
    const rowsPro = document.querySelectorAll('.table-view-pro');
    const rowsProbtn = document.querySelectorAll('.toggle-view');
    rowsProbtn.length > 0 && viewAll.forEach(viewElem => {
        viewElem.addEventListener('click', () => {
            // Determine if we are showing or hiding the rows
            const isShowingAll = viewElem.innerHTML === 'View All';
            rowsPro.forEach(row => {
                if(isShowingAll){
                    row.classList.remove('hidden');    
                }else{
                    row.classList.add('hidden');
                }
            });
            // Determine the current state and toggle both buttons' text accordingly
            viewAll.forEach(button => {
                button.innerHTML = isShowingAll ? 'Hide All' : 'View All'; // Update text on both "View All" buttons
            });
            // Update button text based on the current state
            rowsProbtn.forEach(button => {
                button.innerHTML = isShowingAll ? 'Hide' : 'View'; // Update text for "View" buttons
            });

        });
    });

});
