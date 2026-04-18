<?php

namespace Baseons\Collections;

class System
{
    public static function memory()
    {
        if (self::isLinux()) {
            $output = shell_exec('free -k'); // Obtém memória em KB

            if ($output) {
                $lines = explode("\n", trim($output));
                $memory = $swap = [];

                foreach ($lines as $line) {
                    if (stripos($line, 'Mem:') === 0) {
                        $parts = preg_split('/\s+/', $line);
                        $memory = [
                            'total' => (int)$parts[1] * 1024,
                            'used' => (int)$parts[2] * 1024,
                            'free' => (int)$parts[3] * 1024
                        ];
                    } elseif (stripos($line, 'Swap:') === 0) {
                        $parts = preg_split('/\s+/', $line);
                        $swap = [
                            'total' => (int)$parts[1] * 1024,
                            'used' => (int)$parts[2] * 1024,
                            'free' => (int)$parts[3] * 1024
                        ];
                    }
                }

                return ['memory' => $memory, 'swap' => $swap];
            }
        } elseif (self::isWindows()) {
            $command = 'powershell -command "Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory, TotalVirtualMemorySize, FreeVirtualMemory"';
            $output = shell_exec($command);

            if ($output) {
                preg_match_all('/\d+/', $output, $matches);

                if (count($matches[0]) >= 4) {
                    $totalRAM = (int)$matches[0][0] * 1024;  // Convertendo de KB para Bytes
                    $freeRAM = (int)$matches[0][1] * 1024;
                    $totalSwap = ((int)$matches[0][2] - (int)$matches[0][0]) * 1024; // Virtual - RAM
                    $freeSwap = ((int)$matches[0][3] - (int)$matches[0][1]) * 1024;

                    return [
                        'memory' => [
                            'total' => $totalRAM,
                            'used' => $totalRAM - $freeRAM,
                            'free' => $freeRAM
                        ],
                        'swap' => [
                            'total' => $totalSwap,
                            'used' => $totalSwap - $freeSwap,
                            'free' => $freeSwap
                        ]
                    ];
                }
            }
        }

        return null;
    }

    public static function memoryByPid(int|string $pid)
    {
        if (self::isLinux()) {
            $file = "/proc/$pid/status";
            if (!file_exists($file)) {
                return null;  // Processo não existe ou não tem permissão
            }

            $status = file_get_contents($file);
            $memory = [
                'resident_memory' => 0,  // Memória residente (em bytes) -> fisica ram
                'virtual_memory' => 0,   // Memória virtual (em bytes)
                'swap_memory' => 0,      // Swap usada (em bytes)
                'data_memory' => 0       // Memória de dados (em bytes)
            ];

            // VmRSS: Memória residente
            if (preg_match('/VmRSS:\s+(\d+)\s+kB/', $status, $matches)) {
                $memory['resident_memory'] = (int)$matches[1] * 1024;  // Convertendo para bytes
            }

            // VmSize: Tamanho total da memória virtual
            if (preg_match('/VmSize:\s+(\d+)\s+kB/', $status, $matches)) {
                $memory['virtual_memory'] = (int)$matches[1] * 1024;  // Convertendo para bytes
            }

            // Swap usada
            if (preg_match('/Swap:\s+(\d+)\s+kB/', $status, $matches)) {
                $memory['swap_memory'] = (int)$matches[1] * 1024;  // Convertendo para bytes
            }

            // VmData: Memória usada para dados (em kB)
            if (preg_match('/VmData:\s+(\d+)\s+kB/', $status, $matches)) {
                $memory['data_memory'] = (int)$matches[1] * 1024;  // Convertendo para bytes
            }

            return $memory;
        } elseif (self::isWindows()) {
            $output = [];

            exec("wmic process where ProcessId=$pid get WorkingSetSize", $output);

            if (isset($output[1])) {
                $resident = (int)trim($output[1]); // Memória RAM usada em bytes
            } else {
                return null;
            }

            // Obter swap usada pelo processo via PowerShell
            $output = [];
            exec("powershell -Command \"(Get-Process -Id $pid).PagedMemorySize64\"", $output);

            if (isset($output[0])) {
                $swap = (int)trim($output[0]); // Memória paginada (swap) usada
            } else {
                $swap = 0;
            }

            return [
                'resident_memory' => $resident,
                'swap_memory' => $swap,
                'total_memory' => $resident + $swap
            ];
        }

        return null;
    }

    public static function isLinux()
    {
        return stripos(PHP_OS_FAMILY, 'Linux') !== false;
    }

    public static function isWindows()
    {
        return stripos(PHP_OS_FAMILY, 'Windows') !== false;
    }
}
