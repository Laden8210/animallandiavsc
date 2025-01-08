<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Selection</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
        }
        .container {
            text-align: center;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            margin: 10px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }
        button:hover {
            background-color: #45a049;
        }
        body::before {
            content: "";
            position: fixed; 
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('/images/store.jpg');
            background-size: cover; 
            background-position: center;
            filter: blur(10px); 
            z-index: -1; 
        }
        .white-button {
    display: inline-block;
    background-color: white;
    color: black;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    font-family: Arial, sans-serif;
    font-size: 16px;
}


    </style>
</head>
<body>
    <div class="container">
        <a href="admin/index.php">
            <button>Admin</button>
        </a>
        <a href="staff/index.php">
            <button>Staff</button>
        </a>
        <a href="manager/index.php">
            <button>Manager</button>
        </a><br>
        <br><a href="index.php" class="white-button">Go to Homepage?</a>
    </div>
    
</html>
