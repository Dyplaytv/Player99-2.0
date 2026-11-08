<?php
    $db = new SQLite3("./db/studiolivecode_contato.db");
    if (!$db) {
        $errorMsg = 'Database connection failed.';
    } else {
        $query = $db->query('SELECT title, description FROM contato');
        if ($query) {
            $descriptions = array();
            while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
                $descriptions[] = '<div class="message"><h2 class="message-title">' . $row['title'] . '</h2><p class="message-description">' . $row['description'] . '</p></div>';
            }
        } else {
            $errorMsg = 'Error fetching data from the database.';
        }

        $db->close();
    }
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        /* Reset default margin and padding for all elements */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Apply styles to the body element */
        body {
            display: flex;
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            min-height: 100vh; /* Ensure the body covers at least the viewport height */
            background-color: #215496;
            background-size: cover;
            background-repeat: no-repeat;
        }

        /* Apply styles to the message container */
        .message {
            text-align: center; /* Center the text inside the message */
            padding: 20px; /* Add some spacing around the message */
        }

        .message-title {
            font-size: 24px;
            color: white;
            margin-bottom: 10px;
        }

        .message-description {
            font-size: 18px;
            color: white;
        }
    </style>
</head>
<body bgcolor="#215496">

<?php if (isset($descriptions)) : ?>
    <?php foreach ($descriptions as $description) : ?>
        <?php echo $description; ?>
    <?php endforeach; ?>
<?php else : ?>
    <div class="error-message"><?php echo $errorMsg; ?></div>
<?php endif; ?>

</body>
</html>

