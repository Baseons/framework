<?php

namespace Baseons\Job;

class JobOptions
{
    public function name(string $value)
    {
        $key = array_key_last(JobMemory::$jobs);

        if (empty(JobMemory::$jobs[$key]['name'])) {
            JobMemory::$jobs[$key]['name'] = $value;
        } else {
            JobMemory::$jobs[$key]['name'] .= '.' . $value;
        }

        return $this;
    }

    public function description(string $value)
    {
        $key = array_key_last(JobMemory::$jobs);

        JobMemory::$jobs[$key]['description'] = $value;

        return $this;
    }

    /**
     * Timeout kill
     */
    public function timeout(int|false $minutes)
    {
        $key = array_key_last(JobMemory::$jobs);

        JobMemory::$jobs[$key]['timeout'] = $minutes;

        return $this;
    }

    public function unique(bool $unique = true)
    {
        $key = array_key_last(JobMemory::$jobs);

        JobMemory::$jobs[$key]['unique'] = $unique;

        return $this;
    }

    // $agendamento = "30 14 15 8 5"; // Minutos: 30, Hora: 14, Dia do mês: 15, Mês: 8, Dia da semana: 5 (sexta-feira)
    // $agendamento_array = explode(" ", $agendamento);

    // $minutos = $agendamento_array[0];
    // $hora = $agendamento_array[1];
    // $dia = $agendamento_array[2];
    // $mes = $agendamento_array[3];
    // $dia_da_semana = $agendamento_array[4];

    // $data_hora = mktime($hora, $minutos, 0, $mes, $dia, date('Y'));

    // echo date('d/m/Y H:i:s', $data_hora); // Exibe a data e hora formatadas

}
