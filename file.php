<?php

// BY DINGCLOUD(Bu7) ALL RIGHTS RESERVED
// FILE CONTROLLER PHP 1.3


header("Access-Control-Allow-Origin:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header('Content-Type: application/json');


// 处理操作类型
$response = array();
$time = date('Y-m-d-H-i-s'); //设置时间
$response["time"] = "$time";
//token检测
if (empty($_REQUEST["token"])) {
    $response["status"] = "error";
    $response["message"] = "No token input";
    goto output;
} else {
    $token_input = $_REQUEST["token"];
    $response["input_token"] = $token_input;
    $token = md5(date('YmdHi') * 12);
    if ($token_input != $token) {
        $response["status"] = "error";
        $response["message"] = "Token is not right";
        goto output;
    }
}
//storage是否为空检测
if (empty($_REQUEST["storage"])) {
    $response["status"] = "error";
    $response["message"] = "No select storage";
    goto output;
} else {
    $storage = $_REQUEST["storage"];
    $response["storage"] = $storage;
    if (!is_dir("storages/$storage")) {
        $response["status"] = "error";
        $response["message"] = "No such storage";
        goto output;
    }
}
if (empty($_REQUEST["action"])) {
    $response["status"] = "error";
    $response["message"] = "No select action";
    goto output;
} else {
    $action = $_REQUEST["action"];
    $response["action"] = $action;
    if ($action == "upload") {
        goto upload;
    }
    if ($action == "delete") {
        goto del;
    }
    if ($action == "backup") {
        goto backup;
    }
    $response["status"] = "error";
    $response["message"] = "No such action";
    goto output;
}

upload:
//变量设置(upload)
if (empty($_FILES["file"])) {
    $response["status"] = "error";
    $response["message"] = "No input files";
    goto output;
}
$file = $_FILES["file"];
$file_name = $file["name"];
$new_file_name = $file_name;
$file_size = $file["size"];
$file_type = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
$upload_file_hash = hash_file('sha256', $file);
//add
if (!empty($_POST["folder_name"])) {
    $folder_name = $_POST["folder_name"];
    $response["folder_name"] = "$folder_name";
}
if (!empty($_POST["max_size"])) {
    $max_size = $_POST["max_size"];
    $response["max_size"] = "$max_size";
}
if (!empty($_POST["allowed_file_types"])) {
    $allowed_file_types = $_POST["allowed_file_types"];
    $response["allowed_file_types"] = "$allowed_file_types";
}


//文件检测
// 检查文件大小
if (!empty($_POST["max_size"])) {
    if ($file_size > $max_size) {
        $response["status"] = "error";
        $response["message"] = "File is too big";
        goto output;
    }
}
// 检查文件类型
if (!empty($_POST["allowed_file_types"])) {
    $allowed_file_types = array($allowed_file_types);
    if (!in_array($file_type, $allowed_file_types)) {
        $response["status"] = "error";
        $response["message"] = "This file type is not allowed";
        goto output;
    }
}



// 检查是否生成随机文件名
if (!empty($_POST["file_name_random"]) && $_POST["file_name_random"] == "true") {
    $response["file_name_random"] = $_POST["file_name_random"];
    $new_file_name = uniqid() . "." . $file_type;
}
// 上传后文件夹的路径
$target_dir = "storages/$storage/$folder_name";
// 上传后文件的路径
$target_file = "$target_dir/$new_file_name";
// 创建文件夹
if (!empty($_POST["auto_mkdir"]) && $_POST["auto_mkdir"] == "true") {
    $response["auto_mkdir"] = $_POST["auto_mkdir"];
    if (!is_dir("$target_dir")) {
        mkdir("$target_dir");
    }
}
//检查是否已有文件
if (file_exists($target_file)) {
    if (!empty($_POST["overwrite"]  && $_POST["overwrite"] == "true")) {
        $response["overwrite"] = $_POST["overwrite"];
    } else {
        $response["status"] = "error";
        $response["message"] = "File is already exist,if you want to overwrite it,please add overwrite=true";
        goto output;
    }
} 
// 上传文件 
if (move_uploaded_file($file["tmp_name"], $target_file)) {
    // 文件上传成功
    $http_location = "http://www.xn--7xvw16c.cc/data/$target_file";   
    $response["status"] = "success";
    $response["file_name"] = $file_name;
    $response["file_type"] = $file_type;
    $response["file_size"] = $file_size;
    $response["file_hash_sha256"] = $upload_file_hash;
    $response["relative_dir"] = $target_dir;
    $response["relative_path"] = $target_file;
    $response["http_location"] = $http_location;
    goto output;
} else {
    // 文件上传失败
    $response["status"] = "error";
    $response["message"] = "UnKnown Error";
    goto output;
}
/*
// 修改文件名称
if (!empty($_POST["new_name"])) {
    $new_file_name = $_POST["new_name"];
    $new_target_file = $target_dir . $new_file_name;
    if (rename($target_file, $new_target_file)) {
        $response = array(
            "status" => "success",
            "time" => $time,
            "name" => $new_file_name,
            "location" => $new_target_file,

        );
        echo json_encode($response);
    } else {
        $response = array(
            "status" => "error",
            "time" => $time,
            "message" => "File name change failed"
        );
        echo json_encode($response);
    }
}
*/

// 删除指定位置的文件
del:
if (empty($_POST["delete_path"])) {
    $response["status"] = "error";
    $response["message"] = "No delete path input";
    goto output;
} else {
    $delete_path = $_POST["delete_path"];
    if (file_exists($delete_path)) {
        unlink($delete_path);
        $response["status"] = "success";
        $response["message"] = "File deleted successfully";
        goto output;
    } else {
        $response["status"] = "error";
        $response["message"] = "File deleted successfully";
        $response["message"] = "File does not exist";
        goto output;
    }
}


backup:
// 变量设置
$storage_path = "storages/$storage";
$backup_zip_path = "storages/backup/storages/sto-$storage-$time.zip.bak";
// 使用ZipArchive类进行压缩
$zip = new ZipArchive();
$zip->open($backup_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
// 遍历文件夹并将文件添加到压缩文件中
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storage_path), RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($files as $name => $file) {
    // 只压缩文件，不压缩文件夹
    if (!$file->isDir()) {
        $file_path = $file->getRealPath();
        $relative_path = substr($file_path, strlen($storage_path) + 1);
        $zip->addFile($file_path, $relative_path);
    }
}
// 关闭压缩文件
$zip->close();

// 检测是否直接下载
if (!empty($_REQUEST["direct_download"]) && $_REQUEST["direct_download"] == "true") {
    $response["direct_download"] = $_REQUEST["direct_download"];
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=" . $backup_zip_path);
    header("Content-Length: " . filesize($backup_zip_path));
    readfile($backup_zip_path);
}

$http_location = "http://www.xn--7xvw16c.cc/data/$backup_zip_path"; 
$response["status"] = "success";
$response["message"] = "Storage backup successfully";
$response["http_location"] = $http_location;
goto output;


output:
    echo json_encode($response);

?>