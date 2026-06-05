<!-- C:\xampp\htdocs\smart-plant-pot\db.php -->

<?php

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "smart_plant_pot"
);

if ($conn->connect_error) {
    die("Database connection failed");
}
