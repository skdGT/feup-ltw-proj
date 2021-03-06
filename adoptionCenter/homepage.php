<?php
session_start();
include 'csrf_set.php';
set_csrf();

include_once('../database/pets.php');
include_once('../database/shelters.php');
include_once('../database/users.php');

$pets = getFeaturedPets();
$shelter = getFeaturedShelter();
$allpets = getAllPets();

include('../templates/common/header.php');
include('../templates/common/main_header.php');
include('../templates/pets/homepage.php');
include('../templates/common/footer.php');