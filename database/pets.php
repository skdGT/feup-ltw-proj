<?php
include_once('connection.php');

function getFeaturedPets()
{
    global $db;
    if ($stmt = $db->prepare('SELECT *, (SELECT count(*) FROM Comments WHERE Comments.pet_id = Pets.pet_id) AS Comments FROM Pets ORDER BY Comments DESC')) {
        $stmt->execute();
        $petComments = $stmt->fetch();
        if ($stmt = $db->prepare('SELECT *, (SELECT count(*) FROM ProposalsUser WHERE ProposalsUser.pet_id = Pets.pet_id) AS Proposals FROM Pets ORDER BY Proposals')) {
            $stmt->execute();
            $petProposals = $stmt->fetch();
            return [$petComments, $petProposals];
        }
        else {
            printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
            die;
        }
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getAllPets() {
    global $db;
    if ($stmt = $db->prepare('SELECT * FROM Pets')) {
        $stmt->execute();
        return $stmt->fetchAll();
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getNumberOfProposals($petID) {
    global $db;
    if ($stmt = $db->prepare('SELECT * FROM ProposalsUser WHERE pet_id = :id')) {
        $stmt->bindParam(':id', $petID);
        $stmt->execute();
        return count($stmt->fetchAll());
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getPetProposals($petID) {
    global $db;
    if ($stmt = $db->prepare('
            SELECT *
            FROM ProposalsUser, Users
            WHERE pet_id = :id
            AND Users.user_id = ProposalsUser.user_id
            ORDER BY date DESC')
    ) {
        $stmt->bindParam(':id', $petID);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getPetOwner($petID) {
    global $db;
    if ($stmt = $db->prepare('
        SELECT * 
        FROM Pets, Users_Pets
        WHERE Pets.pet_id = :id
        AND Pets.pet_id = Users_Pets.pet_id')) {
        $stmt->bindParam(':id', $petID);
        $stmt->execute();
        $pets = $stmt->fetch();
        if (!empty($pets)) return [$pets['user_id'], "user"];

        $stmt = $db->prepare('
            SELECT * 
            FROM Pets, Shelters_Pets 
            WHERE Pets.pet_id = :id
            AND Pets.pet_id = Shelters_Pets.pet_id');
        $stmt->bindParam(':id', $petID);
        $stmt->execute();
        $pets = $stmt->fetch();
        return [$pets['shelter_id'], "shelter"];
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getPetByID($petID) {
    global $db;
    if ($stmt = $db->prepare('SELECT * FROM Pets WHERE pet_id = :id')) {
        $stmt->bindParam(':id', $petID);
        $stmt->execute();
        return $stmt->fetch();
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function addPet($username, $name, $image, $species, $size, $color, $gender, $info, $age, $location, $state) {
    global $db;

    $user_id = getSessionId();

    $stmt = $db->prepare('INSERT INTO Pets(name, species, size, color, gender, info, age, location, state) 
            VALUES (:name, :species, :size, :color, :gender, :info, :age, :location, :state)');
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':species', $species);
    $stmt->bindParam(':size', $size);
    $stmt->bindParam(':color', $color);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':info', $info);
    $stmt->bindParam(':age', $age);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':location', $location);

    $stmt->execute();

    $last_pet_id = $db->lastInsertId('pet_id');

    $stmt = $db->prepare("INSERT INTO Pets_Images(pet_id) VALUES(:pet_id)");
    $stmt->bindParam(':pet_id', $last_pet_id);
    $stmt->execute();

    // Get image ID
    $image_id = $db->lastInsertId();

    uploadImage($image, $image_id, "images/pets");

    if (isUser($username)) {
        $stmt = $db->prepare('INSERT INTO Users_Pets(user_id, pet_id) VALUES (:user_id, :pet_id)');
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':pet_id', $last_pet_id);
        $stmt->execute();
    }else{
        $stmt = $db->prepare('INSERT INTO Shelters_Pets(shelter_id, pet_id) VALUES (:shelter_id, :pet_id)');
        $stmt->bindParam(':shelter_id', $user_id);
        $stmt->bindParam(':pet_id', $last_pet_id);
        $stmt->execute();
    }
}

function editPet($pet_id, $name, $image, $species, $size, $color, $gender, $info, $age, $location, $state) {
    global $db;

    $stmt = $db->prepare('UPDATE Pets SET name = :name, species = :species, size = :size, color = :color, gender = :gender, 
                info = :info, age = :age, location = :location, state = :state
WHERE pet_id = :pet_id');
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':species', $species);
    $stmt->bindParam(':size', $size);
    $stmt->bindParam(':color', $color);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':info', $info);
    $stmt->bindParam(':age', $age);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':pet_id', $pet_id);

    $stmt->execute();

    //image_id = session_id
    if ($image['name'] != "") {
        $stmt = $db->prepare("SELECT img_id FROM Pets_Images WHERE pet_id = :pet_id");
        $stmt->bindParam(':pet_id', $pet_id);
        $stmt->execute();
        uploadImage($image, $stmt->fetch()[0], "images/pets/");
    }
}

function getImageByPetId($petID){
    global $db;
    if ($stmt = $db->prepare('SELECT * FROM Pets_Images WHERE pet_id = :id')) {
        $stmt->bindParam(':id', $petID);
        $stmt->execute();
        return $stmt->fetch()[0];
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getPetComments($petID) {
    global $db;
    if ($stmt = $db->prepare('
            SELECT * 
            FROM Comments, Users
            WHERE pet_id = :id
            AND Users.user_id = Comments.user_id
            ORDER BY date DESC')
    ) {
        $stmt->bindParam(':id', $petID);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function addPetComment($pet_id, $user_id, $text) {
    global $db;
    if ($stmt = $db->prepare('INSERT INTO Comments(user_id, pet_id, text, date) VALUES (:user_id, :pet_id, :text, :date)')) {
        $stmt->bindParam(':pet_id', $pet_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':text', $text);
        $time = time();
        $stmt->bindParam(':date', $time);
        $stmt->execute();

        return $pet_id;
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getBreeds() {
    global $db;
    if ($stmt = $db->prepare('SELECT breed FROM Breeds')) {
        $stmt->execute();
        $tempbreeds = $stmt->fetchAll();

        $breeds = [];
        foreach ($tempbreeds as $breed){
            array_push($breeds, $breed["breed"]);
        }
        return $breeds;
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getCommentReplies($commentID) {
    global $db;
    if ($stmt = $db->prepare('SELECT * FROM Answers WHERE comment_id = :id')) {
        $stmt->bindParam(':id', $commentID);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function addPetReply($comment_id, $text, $user_id, $type) {
    global $db;
    if ($stmt = $db->prepare('INSERT INTO Answers(comment_id, date, text, user_id, type) VALUES (:comment_id, :date, :text, :user_id, :type)')) {
        $stmt->bindParam(':comment_id', $comment_id);
        $stmt->bindParam(':text', $text);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':type', $type);
        $time = time();
        $stmt->bindParam(':date', $time);
        $stmt->execute();
        return $comment_id;
    }
    else {
        printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
        die;
    }
}

function getPetColors() {
  global $db;
  if ($stmt = $db->prepare('SELECT color FROM Pets_Colors')) {
    $stmt->execute();
    $tempcolors = $stmt->fetchAll();

    $colors = [];
    foreach ($tempcolors as $color){
      array_push($colors, $color["color"]);
    }
    return $colors;
  }
  else {
    printf('errno: %d, error: %s', $db->errorCode(), $db->errorInfo()[2]);
    die;
  }
}

function acceptProposal($pet_id, $new_owner_id){
    global $db;
    $state = "adopted";
    $stmt = $db->prepare('UPDATE Pets SET state = :state
                                    WHERE pet_id = :pet_id');
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':pet_id', $pet_id);
    $stmt->execute();

    if (isUser($_SESSION['username'])) {
        $stmt = $db->prepare('UPDATE Users_Pets SET user_id = :user_id
                                    WHERE pet_id = :pet_id');
        $stmt->bindParam(':user_id', $new_owner_id);
        $stmt->bindParam(':pet_id', $pet_id);
        $stmt->execute();
    }else{
        $stmt = $db->prepare('DELETE FROM Shelters_Pets WHERE pet_id = :pet_id');
        $stmt->bindParam(':pet_id', $pet_id);
        $stmt->execute();

        $stmt = $db->prepare('INSERT INTO Users_Pets(user_id, pet_id) VALUES (:user_id, :pet_id)');
        $stmt->bindParam(':user_id', $new_owner_id);
        $stmt->bindParam(':pet_id', $pet_id);
        $stmt->execute();
    }

    $state1 = 'denied';
    $stmt = $db->prepare('UPDATE ProposalsUser SET state = :state
                                    WHERE user_id != :user_id AND pet_id = :pet_id');
    $stmt->bindParam(':user_id', $new_owner_id);
    $stmt->bindParam(':pet_id', $pet_id);
    $stmt->bindParam(':state', $state1);
    $stmt->execute();

    $state2 = 'accepted';
    $stmt = $db->prepare('UPDATE ProposalsUser SET state = :state
                                    WHERE user_id = :user_id AND pet_id = :pet_id');
    $stmt->bindParam(':user_id', $new_owner_id);
    $stmt->bindParam(':pet_id', $pet_id);
    $stmt->bindParam(':state', $state2);
    $stmt->execute();

}

function denyProposal($pet_id, $new_owner_id){
    global $db;

    $state = 'denied';
    $stmt = $db->prepare('UPDATE ProposalsUser SET state = :state
                                    WHERE user_id = :user_id AND pet_id = :pet_id');
    $stmt->bindParam(':user_id', $new_owner_id);
    $stmt->bindParam(':pet_id', $pet_id);
    $stmt->bindParam(':state', $state);
    $stmt->execute();

}

function deletePet($pet_id){
    global $db;

    $stmt = $db->prepare('DELETE FROM Pets
                                    WHERE pet_id = :pet_id');
    $stmt->bindParam(':pet_id', $pet_id);
    $stmt->execute();

    $stmt = $db->prepare('DELETE FROM Pets_Images
                                    WHERE pet_id = :pet_id');
    $stmt->bindParam(':pet_id', $pet_id);
    $stmt->execute();

    unlink("../database/images/pets/originals/" . $pet_id . ".jpg");
    unlink("../database/images/pets/thumbs_medium/" . $pet_id . ".jpg");
    unlink("../database/images/pets/thumbs_small/" . $pet_id . ".jpg");

}


