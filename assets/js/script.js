document.addEventListener('DOMContentLoaded', function () {
    // Function to update row numbers and counters
    function updateTableAndCounters() {
        const rows = document.querySelectorAll('#myTable tbody tr');
        let totalCount = 0;
        let validatedCount = 0;

        rows.forEach((row, index) => {
            // Update row number
            row.querySelector('.row-number').textContent = index + 1;

            // Update validation status
            const isValidated = row.getAttribute('data-validated') === 'true';
            if (isValidated) {
                validatedCount++;
            }

            totalCount++;
        });

        const nonValidatedCount = totalCount - validatedCount;

        // Update counters
        document.getElementById('total-count').textContent = totalCount;
        document.getElementById('validated-count').textContent = validatedCount;
        document.getElementById('non-validated-count').textContent = nonValidatedCount;
    }

    // Function to disable buttons
    function disableButtons(row) {
        const validateButton = row.querySelector('.validate-button');
        const reminderButton = row.querySelector('.reminder-button');

        if (validateButton) {
            validateButton.disabled = true;
            validateButton.style.backgroundColor = 'gray';
            validateButton.style.cursor = 'not-allowed';
        }

        if (reminderButton) {
            reminderButton.disabled = true;
            reminderButton.style.backgroundColor = 'gray';
            reminderButton.style.cursor = 'not-allowed';
        }
    }

    // Function to send an email
    function sendEmail(button, type) {
        const email = button.getAttribute('data-email');
        const entryId = button.getAttribute('data-entry-id');

        const formData = new FormData();
        formData.append('email', email);
        formData.append('type', type);
        formData.append('entry_id', entryId);

        fetch('email.php', {
            method: 'POST',
            body: formData,
        })
            .then((response) => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then((data) => {
                const messageContainer = document.createElement('div');
                messageContainer.classList.add('notification');
                messageContainer.innerHTML = data.message;

                if (data.status === 'success') {
                    messageContainer.style.backgroundColor = '#4CAF50'; // Success green
                    messageContainer.style.color = 'white';

                    // Update the row and disable buttons
                    const row = button.closest('tr');
                    row.setAttribute('data-validated', 'true');
                    disableButtons(row);

                    updateTableAndCounters(); // Update counters after validation
                } else {
                    messageContainer.style.backgroundColor = '#f44336'; // Error red
                    messageContainer.style.color = 'white';
                }

                document.body.appendChild(messageContainer);

                setTimeout(() => messageContainer.remove(), 5000);
            })
            .catch((error) => {
                console.error('Error:', error);

                const messageContainer = document.createElement('div');
                messageContainer.classList.add('notification');
                messageContainer.innerHTML = 'An error occurred while sending the email.';
                messageContainer.style.backgroundColor = '#f44336'; // Error red
                messageContainer.style.color = 'white';

                document.body.appendChild(messageContainer);

                setTimeout(() => messageContainer.remove(), 5000);
            });
    }

    // Handle button clicks
    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('validate-button')) {
            sendEmail(event.target, 'paid'); // Send email for paid users
        } else if (event.target.classList.contains('reminder-button')) {
            sendEmail(event.target, 'unpaid'); // Send email for unpaid users
        } else if (event.target.classList.contains('delete-button')) {
            // Handle delete button
            const row = event.target.closest('tr');
            const entryId = event.target.getAttribute('data-entry-id');

            const formData = new FormData();
            formData.append('entry_id', entryId);

            fetch('delete.php', {
                method: 'POST',
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        row.remove();
                        updateTableAndCounters(); // Update counters after deletion
                        alert('Row deleted successfully!');
                    } else {
                        alert('Error deleting row.');
                    }
                })
                .catch((error) => console.error('Error:', error));
        }
    });

    // Initial update of table and counters
    updateTableAndCounters();
});
