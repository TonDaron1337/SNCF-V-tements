<?php
function resizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 600) {
    // Vérifier si le dossier cible existe, sinon le créer
    $targetDir = dirname($targetPath);
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Vérifier si l'extension GD est installée
    if (!extension_loaded('gd')) {
        error_log('Extension GD non installée');
        return false;
    }

    // Vérifier si le fichier source existe
    if (!file_exists($sourcePath)) {
        error_log('Fichier source non trouvé: ' . $sourcePath);
        // Si le fichier source n'existe pas, copier une image par défaut
        $defaultImage = 'public/images/default.jpg';
        if (file_exists($defaultImage)) {
            return copy($defaultImage, $targetPath);
        }
        return false;
    }

    try {
        // Obtenir les informations de l'image
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            error_log('Impossible de lire les dimensions de l\'image: ' . $sourcePath);
            return false;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];

        // Si l'image est plus petite que les dimensions maximales, la copier simplement
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return copy($sourcePath, $targetPath);
        }

        // Calculer les nouvelles dimensions
        if ($width > $height) {
            $newWidth = $maxWidth;
            $newHeight = intval($height * $maxWidth / $width);
        } else {
            $newHeight = $maxHeight;
            $newWidth = intval($width * $maxHeight / $height);
        }

        // Créer l'image source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($sourcePath);
                break;
            default:
                error_log('Type d\'image non supporté');
                return false;
        }

        if (!$sourceImage) {
            error_log('Impossible de créer l\'image source');
            return false;
        }

        // Créer la nouvelle image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        if (!$newImage) {
            error_log('Impossible de créer la nouvelle image');
            imagedestroy($sourceImage);
            return false;
        }

        // Gérer la transparence pour les PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Redimensionner
        if (!imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        )) {
            error_log('Échec du redimensionnement');
            imagedestroy($sourceImage);
            imagedestroy($newImage);
            return false;
        }

        // Sauvegarder l'image
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($newImage, $targetPath, 85);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($newImage, $targetPath, 8);
                break;
        }

        // Libérer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        if (!$success) {
            error_log('Impossible de sauvegarder l\'image redimensionnée');
            return false;
        }

        // Vérifier les permissions du fichier créé
        chmod($targetPath, 0644);

        return true;
    } catch (Exception $e) {
        error_log('Erreur lors du redimensionnement: ' . $e->getMessage());
        return false;
    }
}