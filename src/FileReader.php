<?php
namespace andrewdanilov\filereader;

class FileReader
{
	/**
	 * Обертка для функции fopen().
	 * Открывает файл с учетом кодировок utf-16, utf-8.
	 * Возвращает указатель на файл.
	 * Результат чтения файла будет автоматически
	 * сконвертирован в utf-8.
	 *
	 * @param string $filename
	 * @param string $mode
	 * @return false|resource
	 */
	public static function fopen($filename, $mode='r')
	{
		$handle = fopen($filename, $mode);
		$bom = fread($handle, 2);
		rewind($handle);

		if ($bom === chr(0xff) . chr(0xfe) || $bom === chr(0xfe) . chr(0xff)) {
			stream_filter_append($handle, 'convert.iconv.UTF-16LE/UTF-8');
		}

		return ($handle);
	}

	/**
	 * Поиск последней строки в файле, с исключением строк
	 * которые начинаются на заданные символы
	 *
	 * @param string $filePath
	 * @param string $exceptLineFirstChar
	 * @return false|string|null
	 */
	public static function getLastLine($filePath, $exceptLineFirstChar='\n')
	{
		$lastWord = null;
		$f = static::fopen($filePath);
		if ($f) {
			// ищем с конца к началу
			if (fseek($f, -1, SEEK_END) == 0) { // в конец файла -1 символ перевода строки
				// позиция последнего символа
				$length = ftell($f);
				// последний прочитанный символ
				$last_char = '';
				$pos = $length;
				while ($pos > 0) {
					fseek($f, -1, SEEK_CUR);
					$char = fread($f, 1);
					// если встретился признак конца строки и предыдущий
					// символ не попадает в $exceptLineFirstChar
					if ($char == "\n" && !preg_match('~^[' . $exceptLineFirstChar . ']$~', $last_char)) {
						break;
					}
					$last_char = $char;
					fseek($f, -1, SEEK_CUR);
					$pos--;
				}
				// извлекаем строку с текущей позиции до первого переноса строки
				$lastWord = fgets($f, $length - $pos + 1);
			}
			fclose($f);
		}
		return trim($lastWord);
	}
}