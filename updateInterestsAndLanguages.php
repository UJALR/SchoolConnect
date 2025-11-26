<?php
require_once "includes/auth.php";
require_once "includes/database.php";
require_once "includes/functions.php";

protectPage();
$userId = getUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: userProfile.php");
    exit;
}

// 1. Get Multi-Select Arrays (these contain existing selected items)
$selectedInterests = $_POST['interests'] ?? [];
$selectedLanguages = $_POST['languages'] ?? [];

// 2. Get New Comma-Separated Strings (these contain items the user wants to add)
$newInterestString = $_POST['new_interests'] ?? '';
$newLanguageString = $_POST['new_languages'] ?? '';

// --- Process Interests ---
// Convert new interest string to array and clean it up
$newInterestsArray = array_filter(
    array_map('trim', explode(',', $newInterestString)), 
    'strlen'
);

// Merge selected existing interests with newly added interests
// array_unique() prevents duplicates if a user typed an item that already existed.
$finalInterestsArray = array_unique(
    array_merge($selectedInterests, $newInterestsArray)
);

// --- Process Languages ---
// Convert new language string to array and clean it up
$newLanguagesArray = array_filter(
    array_map('trim', explode(',', $newLanguageString)), 
    'strlen'
);

// Merge selected existing languages with newly added languages
$finalLanguagesArray = array_unique(
    array_merge($selectedLanguages, $newLanguagesArray)
);

// 3. Call the function with the merged and cleaned arrays
updateInterestsAndLanguages($userId, $finalInterestsArray, $finalLanguagesArray);

// Redirect back to the user profile
header("Location: userProfile.php");
exit;