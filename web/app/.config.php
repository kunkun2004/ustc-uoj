<?php
return array (
  'profile' => 
  array (
    'oj-name' => 'Universal Online Judge',
    'oj-name-short' => 'UOJ',
    'administrator' => 'root',
    'admin-email' => 'admin@local_uoj.ac',
    'QQ-group' => '',
    'ICP-license' => '',
  ),
  'database' => 
  array (
    'database' => 'app_uoj233',
    'username' => 'root',
    'password' => 'root',
    'host' => '127.0.0.1',
  ),
  'web' => 
  array (
    'domain' => NULL,
    'main' => 
    array (
      'protocol' => 'http',
      'host' => UOJContext::httpHost(),
      'port' => 80,
    ),
    'blog' => 
    array (
      'protocol' => 'http',
      'host' => UOJContext::httpHost(),
      'port' => 80,
    ),
  ),
  'security' => 
  array (
    'user' => 
    array (
      'client_salt' => '0QWUvhvoNIkuefaXuMk7j08xkRJd530x',
    ),
    'cookie' => 
    array (
      'checksum_salt' => 
      array (
        0 => 'SF1b9zqXqH63SOcQ',
        1 => 'zs80mkVXu4nmt9EU',
        2 => 'pNgDs9CQ4x6tsHF4',
      ),
    ),
  ),
  'mail' => 
  array (
    'noreply' => 
    array (
      'username' => 'noreply@local_uoj.ac',
      'password' => '_mail_noreply_password_',
      'host' => 'smtp.local_uoj.ac',
      'secure' => 'tls',
      'port' => 587,
    ),
  ),
  'judger' => 
  array (
    'socket' => 
    array (
      'port' => '2333',
      'password' => 'JUuxfn5Zz0cB2ZlhKo8Wiqg0SZ3zYTGw',
    ),
  ),
  'switch' => 
  array (
    'web-analytics' => false,
    'blog-domain-mode' => 3,
  ),
);
