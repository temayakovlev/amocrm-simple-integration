<?php
function outputResponse($status, $message) {
    echo json_encode(array(
        'status' => ($status ? 'ok' : 'fail'),
        'message' => $message
    ));
    exit();
};
?>