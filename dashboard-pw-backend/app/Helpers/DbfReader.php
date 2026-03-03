<?php

namespace App\Helpers;

/**
 * Simple DBF File Reader
 * Supports dBASE III/IV format
 */
class DbfReader
{
    protected $handle;
    protected $header;
    protected $fields = [];
    protected $recordCount = 0;
    protected $headerSize = 0;
    protected $recordSize = 0;
    protected $encoding;

    public function __construct(string $filePath, string $encoding = 'cp850')
    {
        if (!file_exists($filePath)) {
            throw new \Exception("DBF file not found: $filePath");
        }

        $this->handle = fopen($filePath, 'rb');
        if (!$this->handle) {
            throw new \Exception("Cannot open DBF file: $filePath");
        }

        $this->encoding = $encoding;
        $this->readHeader();
    }

    protected function readHeader(): void
    {
        // Read first 32 bytes (header)
        $header = fread($this->handle, 32);
        $info = unpack('Cversion/Cyear/Cmonth/Cday/Vrecords/vheadersize/vrecordsize', $header);

        $this->recordCount = $info['records'];
        $this->headerSize = $info['headersize'];
        $this->recordSize = $info['recordsize'];

        // Read field descriptors (32 bytes each, until 0x0D terminator)
        $fieldOffset = 1; // offset in record (1 for deleted flag)
        while (true) {
            $fieldData = fread($this->handle, 32);
            if (!$fieldData || ord($fieldData[0]) === 0x0D) {
                break;
            }

            $field = unpack('A11name/Atype/Voffset/Clength/Cdecimal', $fieldData);
            $field['name'] = rtrim($field['name'], "\x00");
            $field['field_offset'] = $fieldOffset;
            $fieldOffset += $field['length'];
            $this->fields[] = $field;
        }

        // Seek to first record
        fseek($this->handle, $this->headerSize);
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getRecordCount(): int
    {
        return $this->recordCount;
    }

    /**
     * Read all records as array
     */
    public function readAll(): array
    {
        fseek($this->handle, $this->headerSize);
        $records = [];
        for ($i = 0; $i < $this->recordCount; $i++) {
            $record = $this->readRecord();
            if ($record !== null) {
                $records[] = $record;
            }
        }
        return $records;
    }

    /**
     * Iterate records using a callback (memory efficient)
     */
    public function each(callable $callback): void
    {
        fseek($this->handle, $this->headerSize);
        for ($i = 0; $i < $this->recordCount; $i++) {
            $record = $this->readRecord();
            if ($record !== null) {
                $callback($record, $i);
            }
        }
    }

    protected function readRecord(): ?array
    {
        if (feof($this->handle)) {
            return null;
        }

        $rawRecord = fread($this->handle, $this->recordSize);
        if (!$rawRecord || strlen($rawRecord) < $this->recordSize) {
            return null;
        }

        // First byte is delete flag (* = deleted, space = active)
        if ($rawRecord[0] === '*') {
            return null; // skip deleted records
        }

        $record = [];
        $pos = 1;
        foreach ($this->fields as $field) {
            $rawValue = substr($rawRecord, $pos, $field['length']);
            $pos += $field['length'];

            // Convert encoding
            $value = iconv($this->encoding, 'UTF-8//IGNORE', $rawValue);
            $value = trim($value);

            // Type conversion
            switch ($field['type']) {
                case 'N': // Numeric
                case 'F': // Float
                    $value = $value === '' ? null : (float) $value;
                    if ($value !== null && $field['decimal'] === 0) {
                        $value = (int) $value;
                    }
                    break;
                case 'L': // Logical
                    $value = in_array(strtoupper($value), ['T', 'Y', '1']) ? 1 : 0;
                    break;
                case 'D': // Date YYYYMMDD
                    if (strlen($value) === 8 && $value !== '        ') {
                        $value = substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2);
                    } else {
                        $value = null;
                    }
                    break;
                case 'C': // Character
                    $value = $value === '' ? null : $value;
                    break;
            }

            $record[strtolower($field['name'])] = $value;
        }

        return $record;
    }

    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
