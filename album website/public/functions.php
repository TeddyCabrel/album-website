<?php
session_start();
include_once 'config.php';
// Connect to MySQL database function
function pdo_connect_mysql() {
    try {
    	$pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=utf8', db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// If there is an error with the connection, stop the script and display the error.
    	exit('Failed to connect to database!');
    }
    return $pdo;
}
// Convert filesize to a readable format
function convert_filesize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}
// Convert SVG to PNG
function convert_svg_to_png($source) {
    // The ImageMagick PHP extension is required to convert SVG images 
    if (class_exists('Imagick')) {
        $im = new Imagick();
        // Fetch the SVG file
        $svg = file_get_contents($source);
        // Ensure the background is transparent
        $im->setBackgroundColor(new ImagickPixel('transparent'));
        // Read and process the SVG image
        $im->readImageBlob($svg);
        // Set type as PNG
        $im->setImageFormat('png24');
        // Determine the new path
        $new_path = substr_replace($source, 'png', strrpos($source , '.')+1);
        // Write image to file
        $im->writeImage($new_path);
        // Clean up
        $im->clear();
        $im->destroy();
        // Delete the old file
        unlink($source);
        // return the new path
        return $new_path;
    } else {
        exit('The ImageMagick PHP extension is required to convert SVG images to PNG images!');
    }
}
// Create image thumbnails for image media files
function create_image_thumbnail($source, $id) {
    $info = getimagesize($source);
	$image_width = $info[0];
	$image_height = $info[1];
	$new_width = $image_width;
	$new_height = $image_height;
    $thumbnail_parts = explode('.', $source);
	$thumbnail_path = 'media/thumbnails/' . $id . '.' . end($thumbnail_parts);
	if ($image_width > auto_generate_image_thumbnail_max_width || $image_height > auto_generate_image_thumbnail_max_height) {
		if ($image_width > $image_height) {
	    	$new_height = floor(($image_height/$image_width)*auto_generate_image_thumbnail_max_width);
  			$new_width  = auto_generate_image_thumbnail_max_width;
		} else {
			$new_width  = floor(($image_width/$image_height)*auto_generate_image_thumbnail_max_height);
			$new_height = auto_generate_image_thumbnail_max_height;
		}
	}
    if ($info['mime'] == 'image/jpeg') {
        $img = imagescale(imagecreatefromjpeg($source), $new_width, $new_height);
        imagejpeg($img, $thumbnail_path);
    } else if ($info['mime'] == 'image/webp') {
        $img = imagescale(imagecreatefromwebp($source), $new_width, $new_height);
        imagewebp($img, $thumbnail_path);
    } else if ($info['mime'] == 'image/png') {
        $img = imagescale(imagecreatefrompng($source), $new_width, $new_height);
        imagepng($img, $thumbnail_path);
    }
    return $thumbnail_path;
}
// Compress image function
function compress_image($source, $quality) {
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') {
        imagejpeg(imagecreatefromjpeg($source), $source, $quality);
    } else if ($info['mime'] == 'image/webp') {
        imagewebp(imagecreatefromwebp($source), $source, $quality);
    } else if ($info['mime'] == 'image/png') {
        $png_quality = 9 - floor($quality/10);
        $png_quality = $png_quality < 0 ? 0 : $png_quality;
        $png_quality = $png_quality > 9 ? 9 : $png_quality;
        imagepng(imagecreatefrompng($source), $source, $png_quality);
    }
}
// Correct image orientation function
function correct_image_orientation($source) {
    if (strpos(strtolower($source), '.jpg') == false && strpos(strtolower($source), '.jpeg') == false) return;
    $exif = exif_read_data($source);
    $info = getimagesize($source);
    if ($exif && isset($exif['Orientation'])) {
        if ($exif['Orientation'] && $exif['Orientation'] != 1) {
            if ($info['mime'] == 'image/jpeg') {
                $img = imagecreatefromjpeg($source);
            } else if ($info['mime'] == 'image/webp') {
                $img = imagecreatefromwebp($source);
            } else if ($info['mime'] == 'image/png') {
                $img = imagecreatefrompng($source);
            }
            $deg = 0;
            $deg = $exif['Orientation'] == 3 ? 180 : $deg;
            $deg = $exif['Orientation'] == 6 ? 90 : $deg;
            $deg = $exif['Orientation'] == 8 ? -90 : $deg;
            if ($deg) {
                $img = imagerotate($img, $deg, 0);
                if ($info['mime'] == 'image/jpeg') {
                    imagejpeg($img, $source);
                } else if ($info['mime'] == 'image/webp') {
                    imagewebp($img, $source);
                } else if ($info['mime'] == 'image/png') {
                    imagepng($img, $source);
                }
            }
        }
    }
}
// Resize image function
function resize_image($source, $max_width, $max_height) {
    $info = getimagesize($source);
	$image_width = $info[0];
	$image_height = $info[1];
	$new_width = $image_width;
	$new_height = $image_height;
	if ($image_width > $max_width || $image_height > $max_height) {
		if ($image_width > $image_height) {
	    	$new_height = floor(($image_height/$image_width)*$max_width);
  			$new_width  = $max_width;
		} else {
			$new_width  = floor(($image_width/$image_height)*$max_height);
			$new_height = $max_height;
		}
	}
    if ($info['mime'] == 'image/jpeg') {
        $img = imagescale(imagecreatefromjpeg($source), $new_width, $new_height);
        imagejpeg($img, $source);
    } else if ($info['mime'] == 'image/webp') {
        $img = imagescale(imagecreatefromwebp($source), $new_width, $new_height);
        imagewebp($img, $source);
    } else if ($info['mime'] == 'image/png') {
        $img = imagescale(imagecreatefrompng($source), $new_width, $new_height);
        imagepng($img, $source);
    }
}
// Template header, feel free to customize this
function template_header($title) {
    $admin_link = isset($_SESSION['account_loggedin']) && $_SESSION['account_role'] == 'Admin' ? '<a href="admin/index.php" target="_blank"><i class="fas fa-lock"></i>Admin</a>' : '';
    $logout_link = isset($_SESSION['account_loggedin']) ? '<a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Se déconnecter</a>' : '';
// Indenting the code below will result in an error
echo <<<EOT
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>$title</title>
		<link href="style.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer">
	</head>
	<body>
    <nav class="navtop">
    	<div>
    		<h1><a href="index.php">Album Photo</a></h1>
            <a href="index.php"><i class="fas fa-photo-video"></i>Photos</a>
            <a href="upload.php"><i class="fas fa-upload"></i>Poster</a>
            <a href="collections.php"><i class="fas fa-user"></i>Mes Albums</a>
            $admin_link
            $logout_link
    	</div>
    </nav>
EOT;
}
// Template footer
function template_footer() {
// Indenting the code below will result in an error
echo <<<EOT
        <script src="script.js"></script>
    </body>
</html>
EOT;
}
?>