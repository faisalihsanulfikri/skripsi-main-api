<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EncryptionController extends Controller
{
    public function encryption($plaintext)
    {
        $cipherkey = "qwerty123456wasd";

        // validation plaintext
        if (!$plaintext) {
            return response()->json([
                'success' => '0',
                'plaintext' => 'plaintext tidak ditemukan.'
            ]);
        } elseif (strlen($plaintext) != 16) {
            return response()->json([
                'success' => '0',
                'plaintext' => 'plaintext harus 16 byte atau 16 karakter.'
            ]);
        }
        
        $plaintextHex = $this->plaintextToHex($plaintext);
        $cipherkeyHex = $this->cipherkeyToHex($cipherkey);

        /**
         * Key Sckedule
         */
        $round = '01';
        $keySchedule[0] = $this->keySchedule($cipherkeyHex,$round);
        
        for ($i=1; $i < 10; $i++) { 
            $round = $this->roundConstant($i);
            $keySchedule[$i] = $this->keySchedule($keySchedule[$i-1],$round);
        }

        /**
         * Initial Round
         */

        // AddRoundKey
        $state = $this->addRoundKey($plaintextHex, $cipherkeyHex);

        /**
         * Round 1 - 9
         */

        for ($i=0; $i < 9; $i++) { 
            // SubBytes
            $state = $this->subBytes($state);
            
            // ShiftRows
            $state = $this->shiftRows($state);
            
            // MixColumn
            $state = $this->mixColumn($state);

            // AddRoundKey
            $state = $this->addRoundKey($state, $keySchedule[$i]);
        }

        /**
         * Final Round
         */

         // SubBytes
        $state = $this->subBytes($state);
        
        // ShiftRows
        $state = $this->shiftRows($state);
        
        // AddRoundKey
        $state = $this->addRoundKey($state, $keySchedule[9]);

        /**
         * Finish
         */
        
        $ciphertext = $this->ciphertext($state);

        return response()->json([
            'ciphertext' => $ciphertext
        ]);
    }

    public function plaintextToHex($plaintext)
    {
        $plaintext = str_split($plaintext);

        $k = 0;
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                $plaintextHex[$i][$j] = strtoupper(bin2hex($plaintext[$k]));
                $k++;
            }
        }

        return $plaintextHex;
    }

    public function cipherkeyToHex($cipherkey)
    {
        $cipherkey = str_split($cipherkey);

        $k = 0;
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                $cipherkeyHex[$i][$j] = strtoupper(bin2hex($cipherkey[$k]));
                $k++;
            }
        }

        return $cipherkeyHex;
    }

    public function keySchedule($key, $round)
    {
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($i == 0 && $j == 0) {
                    $part = substr($key[$i][$j],0,1);
                    $getRound = substr($round,0,1);
                    $sub = substr($this->sBox($key[$i+3][$j+1]),0,1);
                    $subSchedule = $this->xorCalculate($sub.$part);
                    $keyLSchedule[$i][$j] = $this->xorCalculate($subSchedule.$getRound);
                }

                if ($i == 0 && $j > 0 && $j < 3) {
                    $part = substr($key[$i][$j],0,1);
                    $sub = substr($this->sBox($key[$i+3][$j+1]),0,1);
                    $keyLSchedule[$i][$j] = $this->xorCalculate($sub.$part);
                }

                if ($i == 0 && $j == 3) {
                    $part = substr($key[$i][$j],0,1);
                    $sub = substr($this->sBox($key[$i+3][$j-3]),0,1);
                    $keyLSchedule[$i][$j] = $this->xorCalculate($sub.$part);
                }

                if ($i > 0) {
                    $before = substr($keyLSchedule[$i-1][$j],0,1);
                    $part = substr($key[$i][$j],0,1);
                    $keyLSchedule[$i][$j] = $this->xorCalculate($before.$part);
                }
            }
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($i == 0 && $j == 0) {
                    $part = substr($key[$i][$j],-1);
                    $getRound = substr($round,-1);
                    $sub = substr($this->sBox($key[$i+3][$j+1]),-1);
                    $subSchedule = $this->xorCalculate($sub.$part);
                    $keyRSchedule[$i][$j] = $this->xorCalculate($subSchedule.$getRound);
                }

                if ($i == 0 && $j > 0 && $j < 3) {
                    $part = substr($key[$i][$j],-1);
                    $sub = substr($this->sBox($key[$i+3][$j+1]),-1);
                    $keyRSchedule[$i][$j] = $this->xorCalculate($sub.$part);
                }

                if ($i == 0 && $j == 3) {
                    $part = substr($key[$i][$j],-1);
                    $sub = substr($this->sBox($key[$i+3][$j-3]),-1);
                    $keyRSchedule[$i][$j] = $this->xorCalculate($sub.$part);
                }

                if ($i > 0) {
                    $before = substr($keyRSchedule[$i-1][$j],-1);
                    $part = substr($key[$i][$j],-1);
                    $keyRSchedule[$i][$j] = $this->xorCalculate($before.$part);
                }
            }
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($i == 0 && $j == 0) {
                    $keySchedule[$i][$j] = $keyLSchedule[$i][$j].$keyRSchedule[$i][$j];
                }
                if ($i == 0 && $j > 0 && $j < 3) {
                    $keySchedule[$i][$j] = $keyLSchedule[$i][$j].$keyRSchedule[$i][$j];
                }
                if ($i == 0 && $j == 3) {
                    $keySchedule[$i][$j] = $keyLSchedule[$i][$j].$keyRSchedule[$i][$j];
                }
                if ($i > 0) {
                    $keySchedule[$i][$j] = $keyLSchedule[$i][$j].$keyRSchedule[$i][$j];
                }
                
            }
        }
        
        return $keySchedule;
    }

    public function addRoundKey($state, $roundKey)
    {
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) {
                $xor[$i][$j] = $this->xor($state[$i][$j], $roundKey[$i][$j]);
                $state[$i][$j] = strtoupper(str_pad($xor[$i][$j], 2, "0", STR_PAD_LEFT));
            }
        }

        return $state;
    }

    public function subBytes($state)
    {
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) {
                $subBytes[$i][$j] = $this->sBox($state[$i][$j]);
                $state[$i][$j] = strtoupper(str_pad($subBytes[$i][$j], 2, "0", STR_PAD_LEFT));
            }
        }

        return $state;
    }

    public function shiftRows($state)
    {
        // segitiga atas
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) {
                
                if ($i == 0 && $j <= 3) {
                    $shiftRows[$i][$j] = $state[$i + $j][$j];
                }

                if ($i == 1 && $j <= 2) {
                    $shiftRows[$i][$j] = $state[$i + $j][$j];
                }

                if ($i == 2 && $j <= 1) {
                    $shiftRows[$i][$j] = $state[$i + $j][$j];
                }

                if ($i == 3 && $j <= 0) {
                    $shiftRows[$i][$j] = $state[$i + $j][$j];
                }
            }
        }

        // segitiga bawah
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) {

                if ($i == 1 && $j > 2) {
                    $shiftRows[$i][$j] = $state[$i - $i][$j];
                }

                if ($i == 2 && $j > 1) {
                    $shiftRows[$i][$j] = $state[$i - 4 + $j][$j];
                }

                if ($i == 3 && $j > 0) {
                    $shiftRows[$i][$j] = $state[$i - $i + $j - 1][$j];
                }
            }
        }
        
        return $shiftRows;
    }

    public function mixColumn($state)
    {
        $mix = [];

        // first rows
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataLeft[$i][$j] = substr($this->multiply02($state[$i][$j]),0,1);
                }
                if ($j == 1) {
                    $dataLeft[$i][$j] = substr($this->multiply03($state[$i][$j]),0,1);
                }
                if ($j == 2) {
                    $dataLeft[$i][$j] = substr($state[$i][$j],0,1);
                }
                if ($j == 3) {
                    $dataLeft[$i][$j] = substr($state[$i][$j],0,1);
                }
            }
            $subLeft1[$i] = $this->xorCalculate($dataLeft[$i][0].$dataLeft[$i][1]);
            $subLeft2[$i] = $this->xorCalculate($dataLeft[$i][2].$dataLeft[$i][3]);
            $mixLeft[$i][0] = $this->xorCalculate($subLeft1[$i].$subLeft2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataRight[$i][$j] = substr($this->multiply02($state[$i][$j]),-1);
                }
                if ($j == 1) {
                    $dataRight[$i][$j] = substr($this->multiply03($state[$i][$j]),-1);
                }
                if ($j == 2) {
                    $dataRight[$i][$j] = substr($state[$i][$j],-1);
                }
                if ($j == 3) {
                    $dataRight[$i][$j] = substr($state[$i][$j],-1);
                }
            }
            $subRight1[$i] = $this->xorCalculate($dataRight[$i][0].$dataRight[$i][1]);
            $subRight2[$i] = $this->xorCalculate($dataRight[$i][2].$dataRight[$i][3]);
            $mixRight[$i][0] = $this->xorCalculate($subRight1[$i].$subRight2[$i]);
        }
        
        for ($i=0; $i < 4; $i++) { 
            $mix[$i][0] = $mixLeft[$i][0].$mixRight[$i][0];
        }

        // second rows
        for ($i=0; $i < 4; $i++) {
            for ($j=0; $j < 4; $j++) {
                if ($j < 3) {
                    $secondState[$i][$j] = $state[$i][$j+1];
                }
                if ($j == 3) {
                    $secondState[$i][$j] = $state[$i][$j-3];
                }
            }
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataLeft[$i][$j] = substr($this->multiply02($secondState[$i][$j]),0,1);
                }
                if ($j == 1) {
                    $dataLeft[$i][$j] = substr($this->multiply03($secondState[$i][$j]),0,1);
                }
                if ($j == 2) {
                    $dataLeft[$i][$j] = substr($secondState[$i][$j],0,1);
                }
                if ($j == 3) {
                    $dataLeft[$i][$j] = substr($secondState[$i][$j],0,1);
                }
            }
            $subLeft1[$i] = $this->xorCalculate($dataLeft[$i][0].$dataLeft[$i][1]);
            $subLeft2[$i] = $this->xorCalculate($dataLeft[$i][2].$dataLeft[$i][3]);
            $mixLeft[$i][1] = $this->xorCalculate($subLeft1[$i].$subLeft2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataRight[$i][$j] = substr($this->multiply02($secondState[$i][$j]),-1);
                }
                if ($j == 1) {
                    $dataRight[$i][$j] = substr($this->multiply03($secondState[$i][$j]),-1);
                }
                if ($j == 2) {
                    $dataRight[$i][$j] = substr($secondState[$i][$j],-1);
                }
                if ($j == 3) {
                    $dataRight[$i][$j] = substr($secondState[$i][$j],-1);
                }
            }
            $subRight1[$i] = $this->xorCalculate($dataRight[$i][0].$dataRight[$i][1]);
            $subRight2[$i] = $this->xorCalculate($dataRight[$i][2].$dataRight[$i][3]);
            $mixRight[$i][1] = $this->xorCalculate($subRight1[$i].$subRight2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            $mix[$i][1] = $mixLeft[$i][1].$mixRight[$i][1];
        }

        // third rows
        for ($i=0; $i < 4; $i++) {
            for ($j=0; $j < 4; $j++) {
                if ($j < 3) {
                    $thirdState[$i][$j] = $secondState[$i][$j+1];
                }
                if ($j == 3) {
                    $thirdState[$i][$j] = $secondState[$i][$j-3];
                }
            }
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataLeft[$i][$j] = substr($this->multiply02($thirdState[$i][$j]),0,1);
                }
                if ($j == 1) {
                    $dataLeft[$i][$j] = substr($this->multiply03($thirdState[$i][$j]),0,1);
                }
                if ($j == 2) {
                    $dataLeft[$i][$j] = substr($thirdState[$i][$j],0,1);
                }
                if ($j == 3) {
                    $dataLeft[$i][$j] = substr($thirdState[$i][$j],0,1);
                }
            }
            $subLeft1[$i] = $this->xorCalculate($dataLeft[$i][0].$dataLeft[$i][1]);
            $subLeft2[$i] = $this->xorCalculate($dataLeft[$i][2].$dataLeft[$i][3]);
            $mixLeft[$i][2] = $this->xorCalculate($subLeft1[$i].$subLeft2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataRight[$i][$j] = substr($this->multiply02($thirdState[$i][$j]),-1);
                }
                if ($j == 1) {
                    $dataRight[$i][$j] = substr($this->multiply03($thirdState[$i][$j]),-1);
                }
                if ($j == 2) {
                    $dataRight[$i][$j] = substr($thirdState[$i][$j],-1);
                }
                if ($j == 3) {
                    $dataRight[$i][$j] = substr($thirdState[$i][$j],-1);
                }
            }
            $subRight1[$i] = $this->xorCalculate($dataRight[$i][0].$dataRight[$i][1]);
            $subRight2[$i] = $this->xorCalculate($dataRight[$i][2].$dataRight[$i][3]);
            $mixRight[$i][2] = $this->xorCalculate($subRight1[$i].$subRight2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            $mix[$i][2] = $mixLeft[$i][2].$mixRight[$i][2];
        }

        // fourth rows
        for ($i=0; $i < 4; $i++) {
            for ($j=0; $j < 4; $j++) {
                if ($j < 3) {
                    $fourthState[$i][$j] = $thirdState[$i][$j+1];
                }
                if ($j == 3) {
                    $fourthState[$i][$j] = $thirdState[$i][$j-3];
                }
            }
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataLeft[$i][$j] = substr($this->multiply02($fourthState[$i][$j]),0,1);
                }
                if ($j == 1) {
                    $dataLeft[$i][$j] = substr($this->multiply03($fourthState[$i][$j]),0,1);
                }
                if ($j == 2) {
                    $dataLeft[$i][$j] = substr($fourthState[$i][$j],0,1);
                }
                if ($j == 3) {
                    $dataLeft[$i][$j] = substr($fourthState[$i][$j],0,1);
                }
            }
            $subLeft1[$i] = $this->xorCalculate($dataLeft[$i][0].$dataLeft[$i][1]);
            $subLeft2[$i] = $this->xorCalculate($dataLeft[$i][2].$dataLeft[$i][3]);
            $mixLeft[$i][3] = $this->xorCalculate($subLeft1[$i].$subLeft2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataRight[$i][$j] = substr($this->multiply02($fourthState[$i][$j]),-1);
                }
                if ($j == 1) {
                    $dataRight[$i][$j] = substr($this->multiply03($fourthState[$i][$j]),-1);
                }
                if ($j == 2) {
                    $dataRight[$i][$j] = substr($fourthState[$i][$j],-1);
                }
                if ($j == 3) {
                    $dataRight[$i][$j] = substr($fourthState[$i][$j],-1);
                }
            }
            $subRight1[$i] = $this->xorCalculate($dataRight[$i][0].$dataRight[$i][1]);
            $subRight2[$i] = $this->xorCalculate($dataRight[$i][2].$dataRight[$i][3]);
            $mixRight[$i][3] = $this->xorCalculate($subRight1[$i].$subRight2[$i]);
        }
        
        for ($i=0; $i < 4; $i++) { 
            $mix[$i][3] = $mixLeft[$i][3].$mixRight[$i][3];
        }

        return $mix;
    }

    public function xor($state, $roundKey)
    {
        $state = str_pad(decbin(hexdec($state)), 8, "0", STR_PAD_LEFT);
        $roundKey = str_pad(decbin(hexdec($roundKey)), 8, "0", STR_PAD_LEFT);

        $state = str_split($state);
        $roundKey = str_split($roundKey);

        for ($i=0; $i < 8; $i++) { 
            $data[$i] = (int)$state[$i] ^ (int)$roundKey[$i];
        }

        $res = '';

        foreach ($data as $j => $el) {
            $res = $res.$el;
        }

        return dechex(bindec($res));
    }

    public function xorMicColumn($bin1,$bin2,$bin3,$bin4)
    {
        $bin1 = str_split($bin1);
        $bin2 = str_split($bin2);
        $bin3 = str_split($bin3);
        $bin4 = str_split($bin4);

        for ($i=0; $i < 8; $i++) { 
            $data[$i] = (int)$bin1[$i] ^ (int)$bin2[$i] ^ (int)$bin3[$i] ^ (int)$bin4[$i];
        }

        $res = '';

        foreach ($data as $j => $el) {
            $res = $res.$el;
        }

        return dechex(bindec($res));
    }    

    public function multiply($dataMixColumn)
    {
        $state = $dataMixColumn['state'];
        $matrix = $dataMixColumn['matrix'];

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                $res[$i][$j] = hexdec($state[0][$j]) * hexdec($matrix[$i][$j]);
                $str_pad[$i][$j] = str_pad(decbin($res[$i][$j]), 8, "0", STR_PAD_LEFT);
                $mix[$i][$j] = substr($str_pad[$i][$j], -8);
            }
        }

        for ($i=0; $i < 4; $i++) { 
            $xorData[$i] = $this->xorMicColumn($mix[$i][0],$mix[$i][1],$mix[$i][2],$mix[$i][3]);
            $xor[$i] = strtoupper(str_pad($xorData[$i], 2, "0", STR_PAD_LEFT));;
        }

        return $xor;
    }

    public function roundConstant($key)
    {
        $roundConstant = array(
            '01',
            '02',
            '04',
            '08',
            '10',
            '20',
            '40',
            '80',
            '1B',
            '36'
        );

        return $roundConstant[$key];

    }

    public function xorCalculate($key)
    {
        $xorCalculate = array(
            '00' => '0',
            '01' => '1',
            '02' => '2',
            '03' => '3',
            '04' => '4',
            '05' => '5',
            '06' => '6',
            '07' => '7',
            '08' => '8',
            '09' => '9',
            '0A' => 'A',
            '0B' => 'B',
            '0C' => 'C',
            '0D' => 'D',
            '0E' => 'E',
            '0F' => 'F',
            '10' => '1',
            '11' => '0',
            '12' => '3',
            '13' => '2',
            '14' => '5',
            '15' => '4',
            '16' => '7',
            '17' => '6',
            '18' => '9',
            '19' => '8',
            '1A' => 'B',
            '1B' => 'A',
            '1C' => 'D',
            '1D' => 'C',
            '1E' => 'F',
            '1F' => 'E',
            '20' => '2',
            '21' => '3',
            '22' => '0',
            '23' => '1',
            '24' => '6',
            '25' => '7',
            '26' => '4',
            '27' => '5',
            '28' => 'A',
            '29' => 'B',
            '2A' => '8',
            '2B' => '9',
            '2C' => 'E',
            '2D' => 'F',
            '2E' => 'C',
            '2F' => 'D',
            '30' => '3',
            '31' => '2',
            '32' => '1',
            '33' => '0',
            '34' => '7',
            '35' => '6',
            '36' => '5',
            '37' => '4',
            '38' => 'B',
            '39' => 'A',
            '3A' => '9',
            '3B' => '8',
            '3C' => 'F',
            '3D' => 'E',
            '3E' => 'D',
            '3F' => 'C',
            '40' => '4',
            '41' => '5',
            '42' => '6',
            '43' => '7',
            '44' => '0',
            '45' => '1',
            '46' => '2',
            '47' => '3',
            '48' => 'C',
            '49' => 'D',
            '4A' => 'E',
            '4B' => 'F',
            '4C' => '8',
            '4D' => '9',
            '4E' => 'A',
            '4F' => 'B',
            '50' => '5',
            '51' => '4',
            '52' => '7',
            '53' => '6',
            '54' => '1',
            '55' => '0',
            '56' => '3',
            '57' => '2',
            '58' => 'D',
            '59' => 'C',
            '5A' => 'F',
            '5B' => 'E',
            '5C' => '9',
            '5D' => '8',
            '5E' => 'B',
            '5F' => 'A',
            '60' => '6',
            '61' => '7',
            '62' => '4',
            '63' => '5',
            '64' => '2',
            '65' => '3',
            '66' => '0',
            '67' => '1',
            '68' => 'E',
            '69' => 'F',
            '6A' => 'C',
            '6B' => 'D',
            '6C' => 'A',
            '6D' => 'B',
            '6E' => '8',
            '6F' => '9',
            '70' => '7',
            '71' => '6',
            '72' => '5',
            '73' => '4',
            '74' => '3',
            '75' => '2',
            '76' => '1',
            '77' => '0',
            '78' => 'F',
            '79' => 'E',
            '7A' => 'D',
            '7B' => 'C',
            '7C' => 'B',
            '7D' => 'A',
            '7E' => '9',
            '7F' => '8',
            '80' => '8',
            '81' => '9',
            '82' => 'A',
            '83' => 'B',
            '84' => 'C',
            '85' => 'D',
            '86' => 'E',
            '87' => 'F',
            '88' => '0',
            '89' => '1',
            '8A' => '2',
            '8B' => '3',
            '8C' => '4',
            '8D' => '5',
            '8E' => '6',
            '8F' => '7',
            '90' => '9',
            '91' => '8',
            '92' => 'B',
            '93' => 'A',
            '94' => 'D',
            '95' => 'C',
            '96' => 'F',
            '97' => 'E',
            '98' => '1',
            '99' => '0',
            '9A' => '3',
            '9B' => '2',
            '9C' => '5',
            '9D' => '4',
            '9E' => '7',
            '9F' => '6',
            'A0' => 'A',
            'A1' => 'B',
            'A2' => '8',
            'A3' => '9',
            'A4' => 'E',
            'A5' => 'F',
            'A6' => 'C',
            'A7' => 'D',
            'A8' => '2',
            'A9' => '3',
            'AA' => '0',
            'AB' => '1',
            'AC' => '6',
            'AD' => '7',
            'AE' => '4',
            'AF' => '5',
            'B0' => 'B',
            'B1' => 'A',
            'B2' => '9',
            'B3' => '8',
            'B4' => 'F',
            'B5' => 'E',
            'B6' => 'D',
            'B7' => 'C',
            'B8' => '3',
            'B9' => '2',
            'BA' => '1',
            'BB' => '0',
            'BC' => '7',
            'BD' => '6',
            'BE' => '5',
            'BF' => '4',
            'C0' => 'C',
            'C1' => 'D',
            'C2' => 'E',
            'C3' => 'F',
            'C4' => '8',
            'C5' => '9',
            'C6' => 'A',
            'C7' => 'B',
            'C8' => '4',
            'C9' => '5',
            'CA' => '6',
            'CB' => '7',
            'CC' => '0',
            'CD' => '1',
            'CE' => '2',
            'CF' => '3',
            'D0' => 'D',
            'D1' => 'C',
            'D2' => 'F',
            'D3' => 'E',
            'D4' => '9',
            'D5' => '8',
            'D6' => 'B',
            'D7' => 'A',
            'D8' => '5',
            'D9' => '4',
            'DA' => '7',
            'DB' => '6',
            'DC' => '1',
            'DD' => '0',
            'DE' => '3',
            'DF' => '2',
            'E0' => 'E',
            'E1' => 'F',
            'E2' => 'C',
            'E3' => 'D',
            'E4' => 'A',
            'E5' => 'B',
            'E6' => '8',
            'E7' => '9',
            'E8' => '6',
            'E9' => '7',
            'EA' => '4',
            'EB' => '5',
            'EC' => '2',
            'ED' => '3',
            'EE' => '0',
            'EF' => '1',
            'F0' => 'F',
            'F1' => 'E',
            'F2' => 'D',
            'F3' => 'C',
            'F4' => 'B',
            'F5' => 'A',
            'F6' => '9',
            'F7' => '8',
            'F8' => '7',
            'F9' => '6',
            'FA' => '5',
            'FB' => '4',
            'FC' => '3',
            'FD' => '2',
            'FE' => '1',
            'FF' => '0'
        );
        
        return $xorCalculate[$key];
    }

    public function sBox($key)
    {
        $sBox = array(
            '00' => '63',
            '01' => '7C',
            '02' => '77',
            '03' => '7B',
            '04' => 'F2',
            '05' => '6B',
            '06' => '6F',
            '07' => 'C5',
            '08' => '30',
            '09' => '01',
            '0A' => '67',
            '0B' => '2B',
            '0C' => 'FE',
            '0D' => 'D7',
            '0E' => 'AB',
            '0F' => '76',
            '10' => 'CA',
            '11' => '82',
            '12' => 'C9',
            '13' => '7D',
            '14' => 'FA',
            '15' => '59',
            '16' => '47',
            '17' => 'F0',
            '18' => 'AD',
            '19' => 'D4',
            '1A' => 'A2',
            '1B' => 'AF',
            '1C' => '9C',
            '1D' => 'A4',
            '1E' => '72',
            '1F' => 'C0',
            '20' => 'B7',
            '21' => 'FD',
            '22' => '93',
            '23' => '26',
            '24' => '36',
            '25' => '3F',
            '26' => 'F7',
            '27' => 'CC',
            '28' => '34',
            '29' => 'A5',
            '2A' => 'E5',
            '2B' => 'F1',
            '2C' => '71',
            '2D' => 'D8',
            '2E' => '31',
            '2F' => '15',
            '30' => '04',
            '31' => 'C7',
            '32' => '23',
            '33' => 'C3',
            '34' => '18',
            '35' => '96',
            '36' => '05',
            '37' => '9A',
            '38' => '07',
            '39' => '12',
            '3A' => '80',
            '3B' => 'E2',
            '3C' => 'EB',
            '3D' => '27',
            '3E' => 'B2',
            '3F' => '75',
            '40' => '09',
            '41' => '83',
            '42' => '2C',
            '43' => '1A',
            '44' => '1B',
            '45' => '6E',
            '46' => '5A',
            '47' => 'A0',
            '48' => '52',
            '49' => '3B',
            '4A' => 'D6',
            '4B' => 'B3',
            '4C' => '29',
            '4D' => 'E3',
            '4E' => '2F',
            '4F' => '84',
            '50' => '53',
            '51' => 'D1',
            '52' => '00',
            '53' => 'ED',
            '54' => '20',
            '55' => 'FC',
            '56' => 'B1',
            '57' => '5B',
            '58' => '6A',
            '59' => 'CB',
            '5A' => 'BE',
            '5B' => '39',
            '5C' => '4A',
            '5D' => '4C',
            '5E' => '58',
            '5F' => 'CF',
            '60' => 'D0',
            '61' => 'EF',
            '62' => 'AA',
            '63' => 'FB',
            '64' => '43',
            '65' => '4D',
            '66' => '33',
            '67' => '85',
            '68' => '45',
            '69' => 'F9',
            '6A' => '02',
            '6B' => '7F',
            '6C' => '50',
            '6D' => '3C',
            '6E' => '9F',
            '6F' => 'A8',
            '70' => '51',
            '71' => 'A3',
            '72' => '40',
            '73' => '8F',
            '74' => '92',
            '75' => '9D',
            '76' => '38',
            '77' => 'F5',
            '78' => 'BC',
            '79' => 'B6',
            '7A' => 'DA',
            '7B' => '21',
            '7C' => '10',
            '7D' => 'FF',
            '7E' => 'F3',
            '7F' => 'D2',
            '80' => 'CD',
            '81' => '0C',
            '82' => '13',
            '83' => 'EC',
            '84' => '5F',
            '85' => '97',
            '86' => '44',
            '87' => '17',
            '88' => 'C4',
            '89' => 'A7',
            '8A' => '7E',
            '8B' => '3D',
            '8C' => '64',
            '8D' => '5D',
            '8E' => '19',
            '8F' => '73',
            '90' => '60',
            '91' => '81',
            '92' => '4F',
            '93' => 'DC',
            '94' => '22',
            '95' => '2A',
            '96' => '90',
            '97' => '88',
            '98' => '46',
            '99' => 'EE',
            '9A' => 'B8',
            '9B' => '14',
            '9C' => 'DE',
            '9D' => '5E',
            '9E' => '0B',
            '9F' => 'DB',
            'A0' => 'E0',
            'A1' => '32',
            'A2' => '3A',
            'A3' => '0A',
            'A4' => '49',
            'A5' => '06',
            'A6' => '24',
            'A7' => '5C',
            'A8' => 'C2',
            'A9' => 'D3',
            'AA' => 'AC',
            'AB' => '62',
            'AC' => '91',
            'AD' => '95',
            'AE' => 'E4',
            'AF' => '79',
            'B0' => 'E7',
            'B1' => 'C8',
            'B2' => '37',
            'B3' => '6D',
            'B4' => '8D',
            'B5' => 'D5',
            'B6' => '4E',
            'B7' => 'A9',
            'B8' => '6C',
            'B9' => '56',
            'BA' => 'F4',
            'BB' => 'EA',
            'BC' => '65',
            'BD' => '7A',
            'BE' => 'AE',
            'BF' => '08',
            'C0' => 'BA',
            'C1' => '78',
            'C2' => '25',
            'C3' => '2E',
            'C4' => '1C',
            'C5' => 'A6',
            'C6' => 'B4',
            'C7' => 'C6',
            'C8' => 'E8',
            'C9' => 'DD',
            'CA' => '74',
            'CB' => '1F',
            'CC' => '4B',
            'CD' => 'BD',
            'CE' => '8B',
            'CF' => '8A',
            'D0' => '70',
            'D1' => '3E',
            'D2' => 'B5',
            'D3' => '66',
            'D4' => '48',
            'D5' => '03',
            'D6' => 'F6',
            'D7' => '0E',
            'D8' => '61',
            'D9' => '35',
            'DA' => '57',
            'DB' => 'B9',
            'DC' => '86',
            'DD' => 'C1',
            'DE' => '1D',
            'DF' => '9E',
            'E0' => 'E1',
            'E1' => 'F8',
            'E2' => '98',
            'E3' => '11',
            'E4' => '69',
            'E5' => 'D9',
            'E6' => '8E',
            'E7' => '94',
            'E8' => '9B',
            'E9' => '1E',
            'EA' => '87',
            'EB' => 'E9',
            'EC' => 'CE',
            'ED' => '55',
            'EE' => '28',
            'EF' => 'DF',
            'F0' => '8C',
            'F1' => 'A1',
            'F2' => '89',
            'F3' => '0D',
            'F4' => 'BF',
            'F5' => 'E6',
            'F6' => '42',
            'F7' => '68',
            'F8' => '41',
            'F9' => '99',
            'FA' => '2D',
            'FB' => '0F',
            'FC' => 'B0',
            'FD' => '54',
            'FE' => 'BB',
            'FF' => '16'
        );
        
        return $sBox[$key];
    }

    public function multiply02($key)
    {
        $multiply02 = array(
            '00' => '00',
            '01' => '02',
            '02' => '04',
            '03' => '06',
            '04' => '08',
            '05' => '0A',
            '06' => '0C',
            '07' => '0E',
            '08' => '10',
            '09' => '12',
            '0A' => '14',
            '0B' => '16',
            '0C' => '18',
            '0D' => '1A',
            '0E' => '1C',
            '0F' => '1E',
            '10' => '20',
            '11' => '22',
            '12' => '24',
            '13' => '26',
            '14' => '28',
            '15' => '2A',
            '16' => '2C',
            '17' => '2E',
            '18' => '30',
            '19' => '32',
            '1A' => '34',
            '1B' => '36',
            '1C' => '38',
            '1D' => '3A',
            '1E' => '3C',
            '1F' => '3E',
            '20' => '40',
            '21' => '42',
            '22' => '44',
            '23' => '46',
            '24' => '48',
            '25' => '4A',
            '26' => '4C',
            '27' => '4E',
            '28' => '50',
            '29' => '52',
            '2A' => '54',
            '2B' => '56',
            '2C' => '58',
            '2D' => '5A',
            '2E' => '5C',
            '2F' => '5E',
            '30' => '60',
            '31' => '62',
            '32' => '64',
            '33' => '66',
            '34' => '68',
            '35' => '6A',
            '36' => '6C',
            '37' => '6E',
            '38' => '70',
            '39' => '72',
            '3A' => '74',
            '3B' => '76',
            '3C' => '78',
            '3D' => '7A',
            '3E' => '7C',
            '3F' => '7E',
            '40' => '80',
            '41' => '82',
            '42' => '84',
            '43' => '86',
            '44' => '88',
            '45' => '8A',
            '46' => '8C',
            '47' => '8E',
            '48' => '90',
            '49' => '92',
            '4A' => '94',
            '4B' => '96',
            '4C' => '98',
            '4D' => '9A',
            '4E' => '9C',
            '4F' => '9E',
            '50' => 'A0',
            '51' => 'A2',
            '52' => 'A4',
            '53' => 'A6',
            '54' => 'A8',
            '55' => 'AA',
            '56' => 'AC',
            '57' => 'AE',
            '58' => 'B0',
            '59' => 'B2',
            '5A' => 'B4',
            '5B' => 'B6',
            '5C' => 'B8',
            '5D' => 'BA',
            '5E' => 'BC',
            '5F' => 'BE',
            '60' => 'C0',
            '61' => 'C2',
            '62' => 'C4',
            '63' => 'C6',
            '64' => 'C8',
            '65' => 'CA',
            '66' => 'CC',
            '67' => 'CE',
            '68' => 'D0',
            '69' => 'D2',
            '6A' => 'D4',
            '6B' => 'D6',
            '6C' => 'D8',
            '6D' => 'DA',
            '6E' => 'DC',
            '6F' => 'DE',
            '70' => 'E0',
            '71' => 'E2',
            '72' => 'E4',
            '73' => 'E6',
            '74' => 'E8',
            '75' => 'EA',
            '76' => 'EC',
            '77' => 'EE',
            '78' => 'F0',
            '79' => 'F2',
            '7A' => 'F4',
            '7B' => 'F6',
            '7C' => 'F8',
            '7D' => 'FA',
            '7E' => 'FC',
            '7F' => 'FE',
            '80' => '1B',
            '81' => '19',
            '82' => '1F',
            '83' => '1D',
            '84' => '13',
            '85' => '11',
            '86' => '17',
            '87' => '15',
            '88' => '0B',
            '89' => '09',
            '8A' => '0F',
            '8B' => '0D',
            '8C' => '03',
            '8D' => '01',
            '8E' => '07',
            '8F' => '05',
            '90' => '3B',
            '91' => '39',
            '92' => '3F',
            '93' => '3D',
            '94' => '33',
            '95' => '31',
            '96' => '37',
            '97' => '35',
            '98' => '2B',
            '99' => '29',
            '9A' => '2F',
            '9B' => '2D',
            '9C' => '23',
            '9D' => '21',
            '9E' => '27',
            '9F' => '25',
            'A0' => '5B',
            'A1' => '59',
            'A2' => '5F',
            'A3' => '5D',
            'A4' => '53',
            'A5' => '51',
            'A6' => '57',
            'A7' => '55',
            'A8' => '4B',
            'A9' => '49',
            'AA' => '4F',
            'AB' => '4D',
            'AC' => '43',
            'AD' => '41',
            'AE' => '47',
            'AF' => '45',
            'B0' => '7B',
            'B1' => '79',
            'B2' => '7F',
            'B3' => '7D',
            'B4' => '73',
            'B5' => '71',
            'B6' => '77',
            'B7' => '75',
            'B8' => '6B',
            'B9' => '69',
            'BA' => '6F',
            'BB' => '6D',
            'BC' => '63',
            'BD' => '61',
            'BE' => '67',
            'BF' => '65',
            'C0' => '9B',
            'C1' => '99',
            'C2' => '9F',
            'C3' => '9D',
            'C4' => '93',
            'C5' => '91',
            'C6' => '97',
            'C7' => '95',
            'C8' => '8B',
            'C9' => '89',
            'CA' => '8F',
            'CB' => '8D',
            'CC' => '83',
            'CD' => '81',
            'CE' => '87',
            'CF' => '85',
            'D0' => 'BB',
            'D1' => 'B9',
            'D2' => 'BF',
            'D3' => 'BD',
            'D4' => 'B3',
            'D5' => 'B1',
            'D6' => 'B7',
            'D7' => 'B5',
            'D8' => 'AB',
            'D9' => 'A9',
            'DA' => 'AF',
            'DB' => 'AD',
            'DC' => 'A3',
            'DD' => 'A1',
            'DE' => 'A7',
            'DF' => 'A5',
            'E0' => 'DB',
            'E1' => 'D9',
            'E2' => 'DF',
            'E3' => 'DD',
            'E4' => 'D3',
            'E5' => 'D1',
            'E6' => 'D7',
            'E7' => 'D5',
            'E8' => 'CB',
            'E9' => 'C9',
            'EA' => 'CF',
            'EB' => 'CD',
            'EC' => 'C3',
            'ED' => 'C1',
            'EE' => 'C7',
            'EF' => 'C5',
            'F0' => 'FB',
            'F1' => 'F9',
            'F2' => 'FF',
            'F3' => 'FD',
            'F4' => 'F3',
            'F5' => 'F1',
            'F6' => 'F7',
            'F7' => 'F5',
            'F8' => 'EB',
            'F9' => 'E9',
            'FA' => 'EF',
            'FB' => 'ED',
            'FC' => 'E3',
            'FD' => 'E1',
            'FE' => 'E7',
            'FF' => 'E5'
        );
        
        return $multiply02[$key];
    }

    public function multiply03($key)
    {
        $multiply03 = array(
            '00' => '00',
            '01' => '03',
            '02' => '06',
            '03' => '05',
            '04' => '0C',
            '05' => '0F',
            '06' => '0A',
            '07' => '09',
            '08' => '18',
            '09' => '1B',
            '0A' => '1E',
            '0B' => '1D',
            '0C' => '14',
            '0D' => '17',
            '0E' => '12',
            '0F' => '11',
            '10' => '30',
            '11' => '33',
            '12' => '36',
            '13' => '35',
            '14' => '3C',
            '15' => '3F',
            '16' => '3A',
            '17' => '39',
            '18' => '28',
            '19' => '2B',
            '1A' => '2E',
            '1B' => '2D',
            '1C' => '24',
            '1D' => '27',
            '1E' => '22',
            '1F' => '21',
            '20' => '60',
            '21' => '63',
            '22' => '66',
            '23' => '65',
            '24' => '6C',
            '25' => '6F',
            '26' => '6A',
            '27' => '69',
            '28' => '78',
            '29' => '7B',
            '2A' => '7E',
            '2B' => '7D',
            '2C' => '74',
            '2D' => '77',
            '2E' => '72',
            '2F' => '71',
            '30' => '50',
            '31' => '53',
            '32' => '56',
            '33' => '55',
            '34' => '5C',
            '35' => '5F',
            '36' => '5A',
            '37' => '59',
            '38' => '48',
            '39' => '4B',
            '3A' => '4E',
            '3B' => '4D',
            '3C' => '44',
            '3D' => '47',
            '3E' => '42',
            '3F' => '41',
            '40' => 'C0',
            '41' => 'C3',
            '42' => 'C6',
            '43' => 'C5',
            '44' => 'CC',
            '45' => 'CF',
            '46' => 'CA',
            '47' => 'C9',
            '48' => 'D8',
            '49' => 'DB',
            '4A' => 'DE',
            '4B' => 'DD',
            '4C' => 'D4',
            '4D' => 'D7',
            '4E' => 'D2',
            '4F' => 'D1',
            '50' => 'F0',
            '51' => 'F3',
            '52' => 'F6',
            '53' => 'F5',
            '54' => 'FC',
            '55' => 'FF',
            '56' => 'FA',
            '57' => 'F9',
            '58' => 'E8',
            '59' => 'EB',
            '5A' => 'EE',
            '5B' => 'ED',
            '5C' => 'E4',
            '5D' => 'E7',
            '5E' => 'E2',
            '5F' => 'E1',
            '60' => 'A0',
            '61' => 'A3',
            '62' => 'A6',
            '63' => 'A5',
            '64' => 'AC',
            '65' => 'AF',
            '66' => 'AA',
            '67' => 'A9',
            '68' => 'B8',
            '69' => 'BB',
            '6A' => 'BE',
            '6B' => 'BD',
            '6C' => 'B4',
            '6D' => 'B7',
            '6E' => 'B2',
            '6F' => 'B1',
            '70' => '90',
            '71' => '93',
            '72' => '96',
            '73' => '95',
            '74' => '9C',
            '75' => '9F',
            '76' => '9A',
            '77' => '99',
            '78' => '88',
            '79' => '8B',
            '7A' => '8E',
            '7B' => '8D',
            '7C' => '84',
            '7D' => '87',
            '7E' => '82',
            '7F' => '81',
            '80' => '9B',
            '81' => '98',
            '82' => '9D',
            '83' => '9E',
            '84' => '97',
            '85' => '94',
            '86' => '91',
            '87' => '92',
            '88' => '83',
            '89' => '80',
            '8A' => '85',
            '8B' => '86',
            '8C' => '8F',
            '8D' => '8C',
            '8E' => '89',
            '8F' => '8A',
            '90' => 'AB',
            '91' => 'A8',
            '92' => 'AD',
            '93' => 'AE',
            '94' => 'A7',
            '95' => 'A4',
            '96' => 'A1',
            '97' => 'A2',
            '98' => 'B3',
            '99' => 'B0',
            '9A' => 'B5',
            '9B' => 'B6',
            '9C' => 'BF',
            '9D' => 'BC',
            '9E' => 'B9',
            '9F' => 'BA',
            'A0' => 'FB',
            'A1' => 'F8',
            'A2' => 'FD',
            'A3' => 'FE',
            'A4' => 'F7',
            'A5' => 'F4',
            'A6' => 'F1',
            'A7' => 'F2',
            'A8' => 'E3',
            'A9' => 'E0',
            'AA' => 'E5',
            'AB' => 'E6',
            'AC' => 'EF',
            'AD' => 'EC',
            'AE' => 'E9',
            'AF' => 'EA',
            'B0' => 'CB',
            'B1' => 'C8',
            'B2' => 'CD',
            'B3' => 'CE',
            'B4' => 'C7',
            'B5' => 'C4',
            'B6' => 'C1',
            'B7' => 'C2',
            'B8' => 'D3',
            'B9' => 'D0',
            'BA' => 'D5',
            'BB' => 'D6',
            'BC' => 'DF',
            'BD' => 'DC',
            'BE' => 'D9',
            'BF' => 'DA',
            'C0' => '5B',
            'C1' => '58',
            'C2' => '5D',
            'C3' => '5E',
            'C4' => '57',
            'C5' => '54',
            'C6' => '51',
            'C7' => '52',
            'C8' => '43',
            'C9' => '40',
            'CA' => '45',
            'CB' => '46',
            'CC' => '4F',
            'CD' => '4C',
            'CE' => '49',
            'CF' => '4A',
            'D0' => '6B',
            'D1' => '68',
            'D2' => '6D',
            'D3' => '6E',
            'D4' => '67',
            'D5' => '64',
            'D6' => '61',
            'D7' => '62',
            'D8' => '73',
            'D9' => '70',
            'DA' => '75',
            'DB' => '76',
            'DC' => '7F',
            'DD' => '7C',
            'DE' => '79',
            'DF' => '7A',
            'E0' => '3B',
            'E1' => '38',
            'E2' => '3D',
            'E3' => '3E',
            'E4' => '37',
            'E5' => '34',
            'E6' => '31',
            'E7' => '32',
            'E8' => '23',
            'E9' => '20',
            'EA' => '25',
            'EB' => '26',
            'EC' => '2F',
            'ED' => '2C',
            'EE' => '29',
            'EF' => '2A',
            'F0' => '0B',
            'F1' => '08',
            'F2' => '0D',
            'F3' => '0E',
            'F4' => '07',
            'F5' => '04',
            'F6' => '01',
            'F7' => '02',
            'F8' => '13',
            'F9' => '10',
            'FA' => '15',
            'FB' => '16',
            'FC' => '1F',
            'FD' => '1C',
            'FE' => '19',
            'FF' => '1A'
        );
        
        return $multiply03[$key];
    }

    public function ciphertext($state)
    {
        $k = 0;
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                $ciphertext[$k] = $state[$i][$j];
                $k++;
            }
        }

        return implode("",$ciphertext);
    }
}
