<?php

$connString = "host=127.0.0.1 port=5433 dbname=MockUp user=postgres password=yayaya123";

$conn = pg_connect($connString);

if (!$conn) {
    echo "KONEKSI GAGAL<br>";
    echo pg_last_error();
} else {
    echo "KONEKSI BERHASIL<br>";

    // Tes query ringan
    $res = pg_query($conn, "SELECT current_database() AS db, current_user AS usr");
    $row = pg_fetch_assoc($res);
    echo "DB: " . $row['db'] . "<br>";
    echo "USER: " . $row['usr'] . "<br>";

    pg_close($conn);
}
