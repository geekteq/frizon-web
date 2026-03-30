<?php

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}
