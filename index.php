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
                    echo "<div class='echo'><p>Username already exists. Please choose a different one</p></div>";
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
                            echo "<div class='echo'><p>Error executing query: " . $stmt->error . "</p></div>";
                        }   
                        $stmt->close();
                    } else {
                        echo "<div class='echo'><p>Error preparing statement: " . $conn->error . "</p></div>";
                    }
                }
                $stmt->close();
            }
        } else {
            echo "<div class='echo'><p>Username cannot be empty</p></div>";
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
                    echo "<div class='echo'><p>No user found with that username!</p></div>";
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
                $filename = $_FILES["file"]["name"];
                $filesize = $_FILES["file"]["size"];
                $filetype = $_FILES["file"]["type"];
    
                // Move the uploaded file to the target directory
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                    // Store the file information in the database
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
        <meta name="description" content="">
        <link rel="icon" sizes="48x48" href="./pic/logoimg.png" type="image/png">
        <meta name="keywords" content="fileshare,copytocopy,shareit,filecopy,filetransfer,mobile to pc">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Qwitcher+Grypen:wght@700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="index.css">
        <title>Copy to Copy</title>
        <style></style>
    </head>
    <body>
        <header>
            <div class="logo-container">
                <img id="logo" src="./pic/logoimg.svg" alt="" srcset="">
                <img id="logotext" src="./pic/logo.svg" alt="" srcset="">
            </div>
        </header>

        <main>
            <div class="user-container">
                <div class="input-container">
                    <div class="form-container" id="registration-form">
                        <form method="post" action="">
                            <input type="text" class="input" id="inputlength" name="username" placeholder="Create the New ID :" pattern=".{8,}" required>
                            <input type="submit" class="input"  name="register" value="Create">
                        </form>
                        <div id="message" >
                            <p id="length" class="invaid">minimum 8 characters</p>
                        </div>
                    </div>
                    
                    <div class="form-container" id="login-form">
                        <form method="post" action="">
                            <input type="text" class="input" name="username" placeholder="Recived the ID :" required>
                            <input type="submit" class="input"  name="login" value="Open">
                        </form>
                    </div>
                </div>

                <div class="echo-container">
                    <?php if (!empty($message)) echo $message; ?>
                </div>

               <div class="wave">
               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                    <path fill="#fff" fill-opacity="1" d="M0,128L21.8,128C43.6,128,87,128,131,112C174.5,96,218,64,262,85.3C305.5,107,349,181,393,208C436.4,235,480,213,524,181.3C567.3,149,611,107,655,106.7C698.2,107,742,149,785,154.7C829.1,160,873,128,916,144C960,160,1004,224,1047,240C1090.9,256,1135,224,1178,192C1221.8,160,1265,128,1309,117.3C1352.7,107,1396,117,1418,122.7L1440,128L1440,320L1418.2,320C1396.4,320,1353,320,1309,320C1265.5,320,1222,320,1178,320C1134.5,320,1091,320,1047,320C1003.6,320,960,320,916,320C872.7,320,829,320,785,320C741.8,320,698,320,655,320C610.9,320,567,320,524,320C480,320,436,320,393,320C349.1,320,305,320,262,320C218.2,320,175,320,131,320C87.3,320,44,320,22,320L0,320Z"></path>
                </svg>
                </div>

            </div>
            
            <div class="form-container" id="upload-form">

                <form method="post" action="" enctype="multipart/form-data">
                    <?php if (isset($_SESSION['user_id'])): ?>

                        <div id="hid-content">
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

                    <div class="btn-container">
                        <button class="Btn" type="submit" value="Upload File" name="upload_file">
                            <span class="icon"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="upload"><path d="M12 4.83582L5.79291 11.0429L7.20712 12.4571L12 7.66424L16.7929 12.4571L18.2071 11.0429L12 4.83582ZM12 10.4857L5.79291 16.6928L7.20712 18.107L12 13.3141L16.7929 18.107L18.2071 16.6928L12 10.4857Z"></path></svg>
                            Upload
                        </button>

                        <button class="Btn" type="button">  
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="refresh"><path d="M5.46257 4.43262C7.21556 2.91688 9.5007 2 12 2C17.5228 2 22 6.47715 22 12C22 14.1361 21.3302 16.1158 20.1892 17.7406L17 12H20C20 7.58172 16.4183 4 12 4C9.84982 4 7.89777 4.84827 6.46023 6.22842L5.46257 4.43262ZM18.5374 19.5674C16.7844 21.0831 14.4993 22 12 22C6.47715 22 2 17.5228 2 12C2 9.86386 2.66979 7.88416 3.8108 6.25944L7 12H4C4 16.4183 7.58172 20 12 20C14.1502 20 16.1022 19.1517 17.5398 17.7716L18.5374 19.5674Z"></path></svg>
                            Refresh
                        </button>

                        <button class="Btn" type="button" id="editBtn" >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="edit"><path d="M12.8995 6.85453L17.1421 11.0972L7.24264 20.9967H3V16.754L12.8995 6.85453ZM14.3137 5.44032L16.435 3.319C16.8256 2.92848 17.4587 2.92848 17.8492 3.319L20.6777 6.14743C21.0682 6.53795 21.0682 7.17112 20.6777 7.56164L18.5563 9.68296L14.3137 5.44032Z"></path></svg>
                            <span class="icon2"></span>
                            Edit
                        </button>

                        <button class="Btn" type="submit" name="delete" id="deleteBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="delete"><path d="M7 4V2H17V4H22V6H20V21C20 21.5523 19.5523 22 19 22H5C4.44772 22 4 21.5523 4 21V6H2V4H7ZM6 6V20H18V6H6ZM9 9H11V17H9V9ZM13 9H15V17H13V9Z"></path></svg>
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
                                <th id="name">Filename</th>
                                <th id="filesize">Filesize</th>
                                <th>Filetype</th>
                                <th id="down">Download</th>
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
                    <div class="logout-container">
                        <a class="logout" href="?logout=true">Logout !</a>
                    </div>
                <?php endif; ?>

            </div>
        </main> 
       
        <footer>
           <div class="footer-container">

                <div class="support" id="Support">
                    <h2>About me</h2>
                    <p >
                        Copy to Copy makes it easy to share files 
                        securely and effortlessly in real time. you 
                        can share a ID to file in your Copy to Copy 
                        cloud storage and control who can view and  
                        edit share files.
                    </p>
                    <ul>
                        <a href="https://policies.google.com/privacy?hl=en-US">Privacy Policy</a>
                        <a href="https://policies.google.com/terms?hl=en-US">Terms & Conditions</a>
                    </ul>
                </div>

                <div class="socialmedia">
                    <h2>FOLLOW UP</h2>
                    <ul>
                        <li>
                            <a href="https://www.facebook.com/login/">
                                <svg  style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;" version="1.1" fill="#ffffff" viewBox="0 0 512 512" width="100%" height="100%" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:serif="http://www.serif.com/" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M449.446,0c34.525,0 62.554,28.03 62.554,62.554l0,386.892c0,34.524 -28.03,62.554 -62.554,62.554l-106.468,0l0,-192.915l66.6,0l12.672,-82.621l-79.272,0l0,-53.617c0,-22.603 11.073,-44.636 46.58,-44.636l36.042,0l0,-70.34c0,0 -32.71,-5.582 -63.982,-5.582c-65.288,0 -107.96,39.569 -107.96,111.204l0,62.971l-72.573,0l0,82.621l72.573,0l0,192.915l-191.104,0c-34.524,0 -62.554,-28.03 -62.554,-62.554l0,-386.892c0,-34.524 28.029,-62.554 62.554,-62.554l386.892,0Z"/></svg>
                                <i class="fab "></i>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.instagram.com/accounts/login/">
                                <svg height="100%" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;" version="1.1" fill="#ffffff" viewBox="0 0 512 512" width="100%" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:serif="http://www.serif.com/" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M449.446,0c34.525,0 62.554,28.03 62.554,62.554l0,386.892c0,34.524 -28.03,62.554 -62.554,62.554l-386.892,0c-34.524,0 -62.554,-28.03 -62.554,-62.554l0,-386.892c0,-34.524 28.029,-62.554 62.554,-62.554l386.892,0Zm-193.446,81c-47.527,0 -53.487,0.201 -72.152,1.053c-18.627,0.85 -31.348,3.808 -42.48,8.135c-11.508,4.472 -21.267,10.456 -30.996,20.184c-9.729,9.729 -15.713,19.489 -20.185,30.996c-4.326,11.132 -7.284,23.853 -8.135,42.48c-0.851,18.665 -1.052,24.625 -1.052,72.152c0,47.527 0.201,53.487 1.052,72.152c0.851,18.627 3.809,31.348 8.135,42.48c4.472,11.507 10.456,21.267 20.185,30.996c9.729,9.729 19.488,15.713 30.996,20.185c11.132,4.326 23.853,7.284 42.48,8.134c18.665,0.852 24.625,1.053 72.152,1.053c47.527,0 53.487,-0.201 72.152,-1.053c18.627,-0.85 31.348,-3.808 42.48,-8.134c11.507,-4.472 21.267,-10.456 30.996,-20.185c9.729,-9.729 15.713,-19.489 20.185,-30.996c4.326,-11.132 7.284,-23.853 8.134,-42.48c0.852,-18.665 1.053,-24.625 1.053,-72.152c0,-47.527 -0.201,-53.487 -1.053,-72.152c-0.85,-18.627 -3.808,-31.348 -8.134,-42.48c-4.472,-11.507 -10.456,-21.267 -20.185,-30.996c-9.729,-9.728 -19.489,-15.712 -30.996,-20.184c-11.132,-4.327 -23.853,-7.285 -42.48,-8.135c-18.665,-0.852 -24.625,-1.053 -72.152,-1.053Zm0,31.532c46.727,0 52.262,0.178 70.715,1.02c17.062,0.779 26.328,3.63 32.495,6.025c8.169,3.175 13.998,6.968 20.122,13.091c6.124,6.124 9.916,11.954 13.091,20.122c2.396,6.167 5.247,15.433 6.025,32.495c0.842,18.453 1.021,23.988 1.021,70.715c0,46.727 -0.179,52.262 -1.021,70.715c-0.778,17.062 -3.629,26.328 -6.025,32.495c-3.175,8.169 -6.967,13.998 -13.091,20.122c-6.124,6.124 -11.953,9.916 -20.122,13.091c-6.167,2.396 -15.433,5.247 -32.495,6.025c-18.45,0.842 -23.985,1.021 -70.715,1.021c-46.73,0 -52.264,-0.179 -70.715,-1.021c-17.062,-0.778 -26.328,-3.629 -32.495,-6.025c-8.169,-3.175 -13.998,-6.967 -20.122,-13.091c-6.124,-6.124 -9.917,-11.953 -13.091,-20.122c-2.396,-6.167 -5.247,-15.433 -6.026,-32.495c-0.842,-18.453 -1.02,-23.988 -1.02,-70.715c0,-46.727 0.178,-52.262 1.02,-70.715c0.779,-17.062 3.63,-26.328 6.026,-32.495c3.174,-8.168 6.967,-13.998 13.091,-20.122c6.124,-6.123 11.953,-9.916 20.122,-13.091c6.167,-2.395 15.433,-5.246 32.495,-6.025c18.453,-0.842 23.988,-1.02 70.715,-1.02Zm0,53.603c-49.631,0 -89.865,40.234 -89.865,89.865c0,49.631 40.234,89.865 89.865,89.865c49.631,0 89.865,-40.234 89.865,-89.865c0,-49.631 -40.234,-89.865 -89.865,-89.865Zm0,148.198c-32.217,0 -58.333,-26.116 -58.333,-58.333c0,-32.217 26.116,-58.333 58.333,-58.333c32.217,0 58.333,26.116 58.333,58.333c0,32.217 -26.116,58.333 -58.333,58.333Zm114.416,-151.748c0,11.598 -9.403,20.999 -21.001,20.999c-11.597,0 -20.999,-9.401 -20.999,-20.999c0,-11.598 9.402,-21 20.999,-21c11.598,0 21.001,9.402 21.001,21Z"/></svg>
                                <i class="fab "></i>
                            </a>
                        </li>
                        <li>
                            <a href="https://x.com/i/flow/login?input_flow_data=%7B%22requested_variant%22%3A%22eyJteCI6IjIifQ%3D%3D%22%7D">
                                <svg height="100%" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"  fill="#ffffff" viewBox="4 10 22 10" width="100%" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:serif="http://www.serif.com/" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M 6 4 C 4.895 4 4 4.895 4 6 L 4 24 C 4 25.105 4.895 26 6 26 L 24 26 C 25.105 26 26 25.105 26 24 L 26 6 C 26 4.895 25.105 4 24 4 L 6 4 z M 8.6484375 9 L 13.259766 9 L 15.951172 12.847656 L 19.28125 9 L 20.732422 9 L 16.603516 13.78125 L 21.654297 21 L 17.042969 21 L 14.056641 16.730469 L 10.369141 21 L 8.8945312 21 L 13.400391 15.794922 L 8.6484375 9 z M 10.878906 10.183594 L 17.632812 19.810547 L 19.421875 19.810547 L 12.666016 10.183594 L 10.878906 10.183594 z"></path></svg>
                                <i class="fab "></i>
                            </a>
                        </li>
                        <li>
                            <a href="https://in.linkedin.com/">
                                <svg height="100%" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;" version="1.1" fill="#ffffff" viewBox="0 0 512 512" width="100%" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:serif="http://www.serif.com/" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M449.446,0c34.525,0 62.554,28.03 62.554,62.554l0,386.892c0,34.524 -28.03,62.554 -62.554,62.554l-386.892,0c-34.524,0 -62.554,-28.03 -62.554,-62.554l0,-386.892c0,-34.524 28.029,-62.554 62.554,-62.554l386.892,0Zm-288.985,423.278l0,-225.717l-75.04,0l0,225.717l75.04,0Zm270.539,0l0,-129.439c0,-69.333 -37.018,-101.586 -86.381,-101.586c-39.804,0 -57.634,21.891 -67.617,37.266l0,-31.958l-75.021,0c0.995,21.181 0,225.717 0,225.717l75.02,0l0,-126.056c0,-6.748 0.486,-13.492 2.474,-18.315c5.414,-13.475 17.767,-27.434 38.494,-27.434c27.135,0 38.007,20.707 38.007,51.037l0,120.768l75.024,0Zm-307.552,-334.556c-25.674,0 -42.448,16.879 -42.448,39.002c0,21.658 16.264,39.002 41.455,39.002l0.484,0c26.165,0 42.452,-17.344 42.452,-39.002c-0.485,-22.092 -16.241,-38.954 -41.943,-39.002Z"/></svg>
                                <i class="fab "></i>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="contacts">
                    <h2>Contact Us</h2>
                    <section class="contact-container">
                <form id="contact-form" >
                <div class="inputbox">
                    <h2>Contact Me!</h2>
                    <div class="inputnameid">
                    <div class="input-file files">
                        <input type="text" name="" id="username" placeholder="Enter your Name:" class="item" autocomplete="off" required>
                    </div>
                    <div class="input-file files">
                        <input type="text" name="" id="userid" placeholder="Enter your ID:" class="item" autocomplete="off" required>
                    </div>
                    </div>
                </div>
                <div class="contactinformation">
                <div class="input-file files">
                    <input type="text" name="" id="useremail" placeholder="Enter your E-mail:" class="item" autocomplete="off" required>
                </div>
                <div class="input-file files">
                    <input type="text" name="" id="subject" placeholder="Subject" class="item" autocomplete="off" required>
                </div>
                <div class="textarea-file files">
                    <textarea name="" id="textmessage" cols="30" rows="10" placeholder="Enter your message" class="item" autocomplete="off" required></textarea>
                </div>
                <button type="submit">
                    <div class="svg-wrapper-1">
                        <div class="svg-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"width="24"height="24">
                                <path fill="none" d="M0 0h24v24H0z"></path>
                                <path fill="currentColor" d="M1.946 9.315c-.522-.174-.527-.455.01-.634l19.087-6.362c.529-.176.832.12.684.638l-5.454 19.086c-.15.529-.455.547-.679.045L12 14l6-8-8 6-8.054-2.685z"></path>
                            </svg>
                        </div>
                    </div>
                    <span>Send</span>
                </button>
                </div>

                
                </form>
        </section>
                    <button id="contbox">contact</button>
                    <a href="mailto:copytocopy0@gmail.com">E-mail : copytocopy@gmail.com</a>   
                </div>
           </div>

            <p class="copyrigth">Copyrigth &copy; 2024 Designed by BHARATH</p>
        
        </footer>
        


        <script src="https://smtpjs.com/v3/smtp.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>                          
        <script type="text/javascript" src="index.js"></script>
        
    </body>

</html>