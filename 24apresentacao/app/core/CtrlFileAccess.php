<?php 

namespace app\core;

class CtrlFileAccess {
	public function isFileAccessible(string $filePath, array $allowedDirs): bool {
		$realFilePath = realpath ( $filePath );
		if ($realFilePath === false || ! is_file ( $realFilePath )) {
			return false;
		}

		foreach ( $allowedDirs as $dir ) {
			$realDirPath = realpath ( $dir );
			if ($realDirPath !== false && str_starts_with ( $realFilePath, $realDirPath . DIRECTORY_SEPARATOR )) {
				return true;
			}
		}

		return false;
	}
	
	function getJsonFile2Array(string $filePath, array $allowedDirs): ?array {
		$fileContent = get_file_contents ( $filePath );
		$data = json_decode ( $fileContent, true );
		if (json_last_error () !== JSON_ERROR_NONE) {
			return json_decode ( '{}', true );
		}
		return $data;
	}	
}

?>