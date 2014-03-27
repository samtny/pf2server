<?php

function get_stats() {
  $stats = array();

  $link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

  $sql = "select count(*) as total from venue where deleted = 0 and approved = 1 and coordinate is not null ";
  $result = mysqli_query($link, $sql);
  if ($result) {
    if ($row = mysqli_fetch_assoc($result)) {
      $stats['venues'] = $row['total'];
    }
  }

  $sql = "select count(*) as total from machine ";
  $result = mysqli_query($link, $sql);
  if ($result) {
    if ($row = mysqli_fetch_assoc($result)) {
      $stats['machines'] = $row['total'];
    }
  }

  $sql = "select count(*) as total from user ";
  $result = mysqli_query($link, $sql);
  if ($result) {
    if ($row = mysqli_fetch_assoc($result)) {
      $stats['users'] = $row['total'];
    }
  }

  $sql = "select count(*) as total from venue where datediff(NOW(), updatedate) between 0 and 30 and deleted = 0 and approved = 1 and coordinate is not null ";
  $result = mysqli_query($link, $sql);
  if ($result) {
    if ($row = mysqli_fetch_assoc($result)) {
      $stats['u30day'] = $row['total'];
    }
  }

  $sql = "select count(*) as total from venue where datediff(NOW(), createdate) between 0 and 30 and deleted = 0 and approved = 1 and coordinate is not null ";
  $result = mysqli_query($link, $sql);
  if ($result) {
    if ($row = mysqli_fetch_assoc($result)) {
      $stats['n30day'] = $row['total'];
    }
  }

  $stats['status'] = 'success';

  return $stats;
}
