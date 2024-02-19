<?php
if ($account['activation_code'] == 'activated') {
	// account is activated
	// Display home page etc
    echo "Dein Account ist aktiviert";
} else {
	// account is not activated
	// redirect user or display an error
    echo "Dein Account ist nicht aktiviert";
}