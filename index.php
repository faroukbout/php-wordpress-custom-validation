<?php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

// Forminator submissions
$form_id = 1; // Forminator form ID
$query = "
    SELECT e.entry_id, e.date_created, m.meta_key, m.meta_value, n.meta_value AS name_value, ip.meta_value AS user_ip
    FROM wp_frmt_form_entry e
    JOIN wp_frmt_form_entry_meta m ON e.entry_id = m.entry_id
    LEFT JOIN wp_frmt_form_entry_meta n ON e.entry_id = n.entry_id AND n.meta_key = 'name-1'
    LEFT JOIN wp_frmt_form_entry_meta ip ON e.entry_id = ip.entry_id AND ip.meta_key = '_forminator_user_ip'
    WHERE e.form_id = $form_id
    ORDER BY name_value ASC
";


$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// results
$submissions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $entry_id = $row['entry_id'];
    if (!isset($submissions[$entry_id])) {
        $submissions[$entry_id]['date_created'] = $row['date_created'];
    }
    $submissions[$entry_id][$row['meta_key']] = $row['meta_value'];
    $submissions[$entry_id]['user_ip'] = $row['user_ip']; // Add button state
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> </title>
    <link rel="stylesheet" href="assets/css/styles.css">

</head>
<body>
<center>
    <div>
        <img src="" alt="entete" width="800">
    </div>
</center>
    <center>
    <h1>Liste </h1>
</center>
<div id="counters">
    <div class="counter">
        <p class="label"> Total</p>
        <span id="total-count" class="value">0</span>
    </div>
    <div class="counter">
        <p class="label">Validées</p>
        <span id="validated-count" class="value">0</span>
    </div>
    <div class="counter">
        <p class="label">Non Validées</p>
        <span id="non-validated-count" class="value">0</span>
    </div>
</div>
    <table id="myTable">
        <thead>
            <tr>
                <th>#</th>
                <th>nom</th>
                <th>prenom</th>
                <th>Email</th>
                <th>telephone</th>
                <th>Date</th>
                <th>recu</th>
                <th>action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $entry_id => $entry): ?>
                <tr data-validated="<?php echo ($entry['user_ip'] === '0') ? 'true' : 'false'; ?>">
                    <td class="row-number"></td>
                    <td><?php echo htmlspecialchars($entry['name-1'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($entry['text-1'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($entry['email-1'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($entry['phone-1'] ?? ''); ?></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($entry['date_created'] ?? '')); ?></td> <!-- Submission date -->
                    <td>
                        <?php 
                        if (!empty($entry['upload-1'])): 
                            // Unserialize the data
                            $file_data = unserialize($entry['upload-1']);
                            
                            // Check if the file data exists and extract the file URL
                            if (isset($file_data['file']['file_url'])):
                                $file_url = htmlspecialchars($file_data['file']['file_url']);
                            ?>
                                <a href="<?php echo $file_url; ?>" target="_blank" style="color: blue; text-decoration: underline;"><img src="<?php echo $file_url; ?>" alt="reçu" width="100"></a>
                            <?php else: ?>
                                <span style="color: red; font-style: italic;">Données de fichier invalides</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: gray; font-style: italic;"></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button 
                            class="validate-button" 
                            data-email="<?php echo htmlspecialchars($entry['email-1'] ?? ''); ?>" 
                            data-entry-id="<?php echo htmlspecialchars($entry_id); ?>"
                            <?php echo ($entry['user_ip'] === '0') ? 'disabled' : ''; ?>
                            style="<?php echo ($entry['user_ip'] === '0') ? 'background-color: gray;' : 'background-color: green; color: white;'; ?>">
                            Valider
                        </button>
                        <button 
                            class="reminder-button" 
                            data-email="<?php echo htmlspecialchars($entry['email-1'] ?? ''); ?>" 
                            data-entry-id="<?php echo htmlspecialchars($entry_id); ?>"
                            <?php echo ($entry['user_ip'] === '0') ? 'disabled' : ''; ?>
                            style="<?php echo ($entry['user_ip'] === '0') ? 'background-color: gray;' : 'background-color: orange; color: white;'; ?>">
                            Sans Paiement
                        </button>
                        <button 
                            class="delete-button" 
                            data-entry-id="<?php echo htmlspecialchars($entry_id); ?>" 
                            style="background-color: red; color: white;">
                            Supprimer
                        </button>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script src="assets/js/script.js"></script>
</body>
</html>
