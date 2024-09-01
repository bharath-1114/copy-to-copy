<?php
    session_start();
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "copytocopy";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Registration Logic
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
        $user = trim($_POST['username']);

        if (!empty($user)) {
            $check_sql = "SELECT id FROM users WHERE username = ?";
            if ($stmt = $conn->prepare($check_sql)) {
                $stmt->bind_param("s", $user);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    echo "<div class='echo'>Username already exists. Please choose a different one.</div>";
                } else {
                    $sql = "INSERT INTO users (username) VALUES (?)";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("s", $user);
                        if ($stmt->execute()) {
                            $_SESSION['user_id'] = $stmt->insert_id;
                            $_SESSION['username'] = $user;
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit();
                        } else {
                            echo "<div class='echo'>Error executing query: " . $stmt->error . "</div>";
                        }   
                        $stmt->close();
                    } else {
                        echo "<div class='echo'>Error preparing statement: " . $conn->error . "</div>";
                    }
                }
                $stmt->close();
            }
        } else {
            echo "<div class='echo'>Username cannot be empty.</div>";
        }
    }

    // Login Logic
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
        $user = trim($_POST['username']);

        if (!empty($user)) {
            $sql = "SELECT id FROM users WHERE username = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $user);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($id);
                    $stmt->fetch();
                    $_SESSION['user_id'] = $id;
                    $_SESSION['username'] = $user;
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    echo "<div class='echo'>No user found with that username!</div>";
                }
                $stmt->close();
            } else {
                echo "<p>Error preparing statement: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>Username cannot be empty.</p>";
        }
    }

    // Logout Logic
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // File Upload and Fetch Logic
    $uploaded_files = [];
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_file'])) {
            if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                $target_dir = "uploads/";
                $target_file = $target_dir . basename($_FILES["file"]["name"]);
                $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                $allowed_types = array("jpg", "jpeg", "png", "gif", "pdf");

                if (!in_array($file_type, $allowed_types)) {
                    $message = "<div class='alert alert-danger'>Sorry, only JPG, JPEG, PNG, GIF, and PDF files are allowed.</div>";
                } else {
                    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                        $filename = $_FILES["file"]["name"];
                        $filesize = $_FILES["file"]["size"];
                        $filetype = $_FILES["file"]["type"];

                        $sql = "INSERT INTO user_files (user_id, filename, filesize, filetype, upload_time) VALUES (?, ?, ?, ?, NOW())";
                        if ($stmt = $conn->prepare($sql)) {
                            $stmt->bind_param("isis", $user_id, $filename, $filesize, $filetype);
                            if ($stmt->execute()) {
                                $message = "<div class='alert alert-success'>The file " . htmlspecialchars($filename) . " has been uploaded and the information has been stored in the database.</div>";
                            } else {
                                $message = "<div class='alert alert-danger'>Sorry, there was an error storing the information in the database: " . $stmt->error . "</div>";
                            }
                            $stmt->close();
                        }
                    } else {
                        $message = "<div class='alert alert-danger'>Sorry, there was an error uploading your file.</div>";
                    }
                }
            } else {
                $message = "<div class='alert alert-warning'>No file selected or an error occurred during upload.</div>";
            }

            // Redirect to the same page to prevent re-submission of form data on refresh
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }


        // Fetch uploaded files
        $sql = "SELECT id, filename, filesize, filetype FROM user_files WHERE user_id = ? ORDER BY id DESC";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $uploaded_files[] = $row;
            }
            $stmt->close();
        } else {
            echo "<p>Error preparing statement: " . $conn->error . "</p>";
        }
    }
    // Close the database connection after all operations are done
    $conn->close();
?>

<?php
    // Database connection details
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "copytocopy";

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Handle filename update request
    if (isset($_POST['edit']) && isset($_POST['file_id']) && isset($_POST['new_filename'])) {
        $file_id = intval($_POST['file_id']);
        $new_filename = trim($_POST['new_filename']);
        $old_filename = trim($_POST['old_filename']);

        // Validate new filename
        if ($new_filename !== '' && $file_id > 0) {
            // Update the filename in the database
            $sql = "UPDATE user_files SET filename = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $new_filename, $file_id);

                if ($stmt->execute()) {
                    // Rename the file on the server
                    $old_file_path = "uploads/" . $old_filename;
                    $new_file_path = "uploads/" . $new_filename;

                    if (file_exists($old_file_path)) {
                        if (!rename($old_file_path, $new_file_path)) {
                            echo "<p>Error renaming the file on the server.</p>";
                        }
                    }
                    // Redirect to avoid form resubmission issues
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    echo "<p>Error updating filename in the database: " . $stmt->error . "</p>";
                }
                $stmt->close();
            } else {
                echo "<p>Error preparing statement: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>Invalid filename or file ID.</p>";
        }
    }

    // Close the database connection
    $conn->close();
?>

<?php
    // Database connection details
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "copytocopy";

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
   
    if (isset($_POST['delete']) && isset($_POST['file_id'])) {
        $file_id = intval($_POST['file_id']);

        // Get file info from the database
        $sql = "SELECT filename FROM user_files WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $stmt->bind_result($filename);
        $stmt->fetch();
        $stmt->close();

        if ($filename) {
            $file_path = "uploads/" . $filename;

            // Delete the file from the server
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Delete the file record from the database
            $sql = "DELETE FROM user_files WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $file_id);
            $stmt->execute();
            $stmt->close();

            // Redirect to avoid form resubmission issues
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
    // Fetch the uploaded files from the database
    $sql = "SELECT * FROM user_files";
    $result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Qwitcher+Grypen:wght@700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="index.css">
      <title>Copy to Copy</title>
    </head>
    <body>
        <header>
            <div class="logo">Copy to Copy</div>
            <div >
                <ul>
                    <li><a href="#">home</a></li>
                    <li><a href="#">about</a></li>
                </ul>
            </div>

        </header>
       <main>
       <div class="user-form">
            <div class="form-container" id="registration-form">
                <form method="post" action="">
                    <input type="text" class="input" name="username" placeholder="Create the New ID :" required>
                    <input type="submit" class="input"  name="register" value="Create">
                </form>
            </div>
            <div class="form-container" id="login-form">
                <form method="post" action="">
                    <input type="text" class="input"  name="username" placeholder="Recived the ID :" required>
                    <input type="submit" class="input"  name="login" value="Open">
                </form>
            </div>
            <div class="wave">
                <!-- <img src="waves.svg" viewBox="0 0 1200 120" preserveAspectRatio="none"></img> -->
            </div>
        </div>

        <div class="form-container" id="upload-form">
            <form method="post" action="" enctype="multipart/form-data">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div id="hid-content"  >
                        <div class="uploadBox">
                            <label for="input-file" id="drop-area">
                                <input type="file" name="file" id="input-file" hidden>
                                <div id="img-view">
                                    <p>Drag and drop or click here <br> to upload File </p>
                                </div>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="btn">
                <button class="Btn" type="submit" value="Upload File" name="upload_file">
                    <img class="upload" src="./pic/upload.svg" alt="arrow-down" />
                    <span class="icon"></span>
                    Upload
                </button>

                <button class="Btn" type="button">
                    <img class="refresh" src="./pic/refresh.svg" alt="arrow-down" />
                    Refresh
                </button>

                <button class="Btn" type="button" id="editBtn" >
                    <img class="edit" src="./pic/edit.svg" alt="arrow-down" />
                    <span class="icon2"></span>
                    Edit
                </button>

                <button class="Btn" type="submit" name="delete" id="deleteBtn">
                    <img class="delete" src="./pic/delete.svg" alt="arrow-down" />
                    <span class="icon2"></span>
                    Delete
                </button>
                <input type="hidden" name="file_id" id="file_id">
                </div>
            </form>
        </div>

         <div class="table-container">
            <h2>Uploaded Files</h2>
            <div class="table-wrapper">
                <table class="table table-fixed">
                    <thead>
                        <tr>
                            <th >Filename</th>
                            <th >Filesize</th>
                            <th>Filetype</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($uploaded_files)): ?>
                        <?php foreach ($uploaded_files as $file): ?>
                        <tr data-id="<?php echo $file['id']; ?>" data-filename="<?php echo htmlspecialchars($file['filename']); ?>">
                            <td class="filename"><?php echo htmlspecialchars($file['filename']); ?></td>
                            <td><?php echo number_format($file['filesize'] / (1024 * 1024), 2); ?> MB</td>
                            <td class="filetypes"><?php echo htmlspecialchars($file['filetype']); ?></td>
                            <td><a href="uploads/<?php echo urlencode($file['filename']); ?>" class="downloadBtn" download>
                                <span class="button__text">Download</span>
                                <span class="button__icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 35 35" id="bdd05811-e15d-428c-bb53-8661459f9307" data-name="Layer 2" class="svg">
                                        <path d="M17.5,22.131a1.249,1.249,0,0,1-1.25-1.25V2.187a1.25,1.25,0,0,1,2.5,0V20.881A1.25,1.25,0,0,1,17.5,22.131Z"></path>
                                        <path d="M17.5,22.693a3.189,3.189,0,0,1-2.262-.936L8.487,15.006a1.249,1.249,0,0,1,1.767-1.767l6.751,6.751a.7.7,0,0,0,.99,0l6.751-6.751a1.25,1.25,0,0,1,1.768,1.767l-6.752,6.751A3.191,3.191,0,0,1,17.5,22.693Z"></path>
                                        <path d="M31.436,34.063H3.564A3.318,3.318,0,0,1,.25,30.749V22.011a1.25,1.25,0,0,1,2.5,0v8.738a.815.815,0,0,0,.814.814H31.436a.815.815,0,0,0,.814-.814V22.011a1.25,1.25,0,1,1,2.5,0v8.738A3.318,3.318,0,0,1,31.436,34.063Z"></path>
                                    </svg>
                                </span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="4">No files uploaded yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="logout">
                    <a class="box" href="?logout=true">Logout !</a>
                </div>
            <?php endif; ?>
        </div>
       </main> 
        <footer>
            <div class="social_media">
                <a href="#" class="socialContainer One">
                    <img class="socialSvg instagramSvg" viewBox="0 0 16 16" id="insta" src="./pic/instagram.png" alt="insta" />
                </a>
                <a href="#" class="socialContainer Two">
                    <img class="socialSvg twitterSvg" viewBox="0 0 16 16" id="linked" src="./pic/linkedin.png" alt="linked" />
                </a>
                <a href="#" class="socialContainer Three">
                    <img class="socialSvg linkdinSvg" viewBox="0 0 448 512" id="twitter" src="./pic/twitter.png" alt="twitter" />
                </a>
                <a href="#" class="socialContainer Four">
                    <img class="socialSvg whatsappSvg" viewBox="0 0 16 16" id="FB" src="./pic/facebook.png" alt="Fb" />
                </a>
            </div>
        </footer>
    <script type="text/javascript" src="index.js">
        
    </script>

   </body>

</html>