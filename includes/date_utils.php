<?php
function formatDateFr($date) {
    setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
    $timestamp = strtotime($date);
    return [
        'date' => date('d/m/Y', $timestamp),
        'time' => date('H\hi', $timestamp)
    ];
}