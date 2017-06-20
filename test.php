<?php
require_once '/home/husnain/Desktop/OW/Projects/Tokens/Token.php';
$token = new Token('12345');
$token = $token->get_unique_token();
echo $token . PHP_EOL;
