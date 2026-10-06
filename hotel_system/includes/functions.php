<?php
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function flash($msg = null, $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'] = ['m' => $msg, 't' => $type]; return null; }
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

function redirect($path) { header('Location: ' . BASE_URL . '/' . $path); exit; }

function money($n) { return '₹' . number_format((float)$n, 2); }

function valid_date($d) {
    $x = DateTime::createFromFormat('Y-m-d', (string)$d);
    return $x && $x->format('Y-m-d') === $d;
}

// True when the room has no Booked/Checked-In booking overlapping [in, out)
function room_is_free(PDO $pdo, $roomId, $in, $out) {
    $s = $pdo->prepare(
        "SELECT COUNT(*) FROM bookings
         WHERE room_id = ? AND status IN ('Booked','Checked-In')
           AND check_in_date < ? AND check_out_date > ?"
    );
    $s->execute([$roomId, $out, $in]);
    return $s->fetchColumn() == 0;
}
