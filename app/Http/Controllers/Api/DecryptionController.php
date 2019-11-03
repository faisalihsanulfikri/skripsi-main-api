<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DecryptionController extends Controller
{
    public function ciphertextToHex($ciphertext)
    {
        $ciphertext = str_split($ciphertext,2);

        $k = 0;
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                $ciphertextHex[$i][$j] = strtoupper($ciphertext[$k]);
                $k++;
            }
        }

        return $ciphertextHex;
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

    public function plaintext($state)
    {
        $k = 0;
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                $plaintext[$k] = hex2bin($state[$i][$j]);
                $k++;
            }
        }

        return implode("",$plaintext);
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

    public function keySchedule($key, $round)
    {
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($i == 0 && $j == 0) {
                    $part = substr($key[$i][$j],0,1);
                    $getRound = substr($round,0,1);
                    $sub = substr($this->sBox($key[$i+3][$j+1]),0,1);
                    $subSchedule = $this->XOR4($sub.$part);
                    $keyLSchedule[$i][$j] = $this->XOR4($subSchedule.$getRound);
                }

                if ($i == 0 && $j > 0 && $j < 3) {
                    $part = substr($key[$i][$j],0,1);
                    $sub = substr($this->sBox($key[$i+3][$j+1]),0,1);
                    $keyLSchedule[$i][$j] = $this->XOR4($sub.$part);
                }

                if ($i == 0 && $j == 3) {
                    $part = substr($key[$i][$j],0,1);
                    $sub = substr($this->sBox($key[$i+3][$j-3]),0,1);
                    $keyLSchedule[$i][$j] = $this->XOR4($sub.$part);
                }

                if ($i > 0) {
                    $before = substr($keyLSchedule[$i-1][$j],0,1);
                    $part = substr($key[$i][$j],0,1);
                    $keyLSchedule[$i][$j] = $this->XOR4($before.$part);
                }
            }
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($i == 0 && $j == 0) {
                    $part = substr($key[$i][$j],-1);
                    $getRound = substr($round,-1);
                    $sub = substr($this->sBox($key[$i+3][$j+1]),-1);
                    $subSchedule = $this->XOR4($sub.$part);
                    $keyRSchedule[$i][$j] = $this->XOR4($subSchedule.$getRound);
                }

                if ($i == 0 && $j > 0 && $j < 3) {
                    $part = substr($key[$i][$j],-1);
                    $sub = substr($this->sBox($key[$i+3][$j+1]),-1);
                    $keyRSchedule[$i][$j] = $this->XOR4($sub.$part);
                }

                if ($i == 0 && $j == 3) {
                    $part = substr($key[$i][$j],-1);
                    $sub = substr($this->sBox($key[$i+3][$j-3]),-1);
                    $keyRSchedule[$i][$j] = $this->XOR4($sub.$part);
                }

                if ($i > 0) {
                    $before = substr($keyRSchedule[$i-1][$j],-1);
                    $part = substr($key[$i][$j],-1);
                    $keyRSchedule[$i][$j] = $this->XOR4($before.$part);
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

    public function inShiftRows($state)
    {
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) {                
                if ($i >= 0 && $j == 0) {
                    $inShiftRows[$i][$j] = $state[$i][$j];
                }

                if ($i == 0 && $j >= 1) {
                    $inShiftRows[$i][$j] = $state[4 - $j][$j];
                }

                if ($i == 1 && $j == 1) {
                    $inShiftRows[$i][$j] = $state[$j - 1][$j];
                }

                if ($i == 1 && $j == 2) {
                    $inShiftRows[$i][$j] = $state[$j + 1][$j];
                }

                if ($i == 1 && $j == 3) {
                    $inShiftRows[$i][$j] = $state[$j - 1][$j];
                }

                if ($i == 2 && $j == 1) {
                    $inShiftRows[$i][$j] = $state[$i - 1][$j];
                }

                if ($i == 2 && $j == 2) {
                    $inShiftRows[$i][$j] = $state[$i - 2][$j];
                }

                if ($i == 2 && $j == 3) {
                    $inShiftRows[$i][$j] = $state[$i + 1][$j];
                }
                
                if ($i == 3 && $j == 1) {
                    $inShiftRows[$i][$j] = $state[$i - 1][$j];
                }

                if ($i == 3 && $j == 2) {
                    $inShiftRows[$i][$j] = $state[$i - 2][$j];
                }

                if ($i == 3 && $j == 3) {
                    $inShiftRows[$i][$j] = $state[$i - 3][$j];
                }
            }
        }

        return $inShiftRows;
    }

    public function inSubBytes($state)
    {
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) {
                $inSubBytes[$i][$j] = $this->inSBox($state[$i][$j]);
                $state[$i][$j] = strtoupper(str_pad($inSubBytes[$i][$j], 2, "0", STR_PAD_LEFT));
            }
        }

        return $state;
    }

    public function inMixColumn($state)
    {
        $inMixColumn = [];

        // first rows
        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataLeft[$i][$j] = substr($this->multiply0E($state[$i][$j]),0,1);
                }
                if ($j == 1) {
                    $dataLeft[$i][$j] = substr($this->multiply0B($state[$i][$j]),0,1);
                }
                if ($j == 2) {
                    $dataLeft[$i][$j] = substr($this->multiply0D($state[$i][$j]),0,1);
                }
                if ($j == 3) {
                    $dataLeft[$i][$j] = substr($this->multiply09($state[$i][$j]),0,1);
                }
            }
            $subLeft1[$i] = $this->XOR4($dataLeft[$i][0].$dataLeft[$i][1]);
            $subLeft2[$i] = $this->XOR4($dataLeft[$i][2].$dataLeft[$i][3]);
            $mixLeft[$i][0] = $this->XOR4($subLeft1[$i].$subLeft2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataRight[$i][$j] = substr($this->multiply0E($state[$i][$j]),-1);
                }
                if ($j == 1) {
                    $dataRight[$i][$j] = substr($this->multiply0B($state[$i][$j]),-1);
                }
                if ($j == 2) {
                    $dataRight[$i][$j] = substr($this->multiply0D($state[$i][$j]),-1);
                }
                if ($j == 3) {
                    $dataRight[$i][$j] = substr($this->multiply09($state[$i][$j]),-1);
                }
            }
            $subRight1[$i] = $this->XOR4($dataRight[$i][0].$dataRight[$i][1]);
            $subRight2[$i] = $this->XOR4($dataRight[$i][2].$dataRight[$i][3]);
            $mixRight[$i][0] = $this->XOR4($subRight1[$i].$subRight2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            $inMixColumn[$i][0] = $mixLeft[$i][0].$mixRight[$i][0];
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
                    $dataLeft[$i][$j] = substr($this->multiply0E($secondState[$i][$j]),0,1);
                }
                if ($j == 1) {
                    $dataLeft[$i][$j] = substr($this->multiply0B($secondState[$i][$j]),0,1);
                }
                if ($j == 2) {
                    $dataLeft[$i][$j] = substr($this->multiply0D($secondState[$i][$j]),0,1);
                }
                if ($j == 3) {
                    $dataLeft[$i][$j] = substr($this->multiply09($secondState[$i][$j]),0,1);
                }
            }
            $subLeft1[$i] = $this->XOR4($dataLeft[$i][0].$dataLeft[$i][1]);
            $subLeft2[$i] = $this->XOR4($dataLeft[$i][2].$dataLeft[$i][3]);
            $mixLeft[$i][1] = $this->XOR4($subLeft1[$i].$subLeft2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataRight[$i][$j] = substr($this->multiply0E($secondState[$i][$j]),-1);
                }
                if ($j == 1) {
                    $dataRight[$i][$j] = substr($this->multiply0B($secondState[$i][$j]),-1);
                }
                if ($j == 2) {
                    $dataRight[$i][$j] = substr($this->multiply0D($secondState[$i][$j]),-1);
                }
                if ($j == 3) {
                    $dataRight[$i][$j] = substr($this->multiply09($secondState[$i][$j]),-1);
                }
            }
            $subRight1[$i] = $this->XOR4($dataRight[$i][0].$dataRight[$i][1]);
            $subRight2[$i] = $this->XOR4($dataRight[$i][2].$dataRight[$i][3]);
            $mixRight[$i][1] = $this->XOR4($subRight1[$i].$subRight2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            $inMixColumn[$i][1] = $mixLeft[$i][1].$mixRight[$i][1];
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
                    $dataLeft[$i][$j] = substr($this->multiply0E($thirdState[$i][$j]),0,1);
                }
                if ($j == 1) {
                    $dataLeft[$i][$j] = substr($this->multiply0B($thirdState[$i][$j]),0,1);
                }
                if ($j == 2) {
                    $dataLeft[$i][$j] = substr($this->multiply0D($thirdState[$i][$j]),0,1);
                }
                if ($j == 3) {
                    $dataLeft[$i][$j] = substr($this->multiply09($thirdState[$i][$j]),0,1);
                }
            }
            $subLeft1[$i] = $this->XOR4($dataLeft[$i][0].$dataLeft[$i][1]);
            $subLeft2[$i] = $this->XOR4($dataLeft[$i][2].$dataLeft[$i][3]);
            $mixLeft[$i][2] = $this->XOR4($subLeft1[$i].$subLeft2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataRight[$i][$j] = substr($this->multiply0E($thirdState[$i][$j]),-1);
                }
                if ($j == 1) {
                    $dataRight[$i][$j] = substr($this->multiply0B($thirdState[$i][$j]),-1);
                }
                if ($j == 2) {
                    $dataRight[$i][$j] = substr($this->multiply0D($thirdState[$i][$j]),-1);
                }
                if ($j == 3) {
                    $dataRight[$i][$j] = substr($this->multiply09($thirdState[$i][$j]),-1);
                }
            }
            $subRight1[$i] = $this->XOR4($dataRight[$i][0].$dataRight[$i][1]);
            $subRight2[$i] = $this->XOR4($dataRight[$i][2].$dataRight[$i][3]);
            $mixRight[$i][2] = $this->XOR4($subRight1[$i].$subRight2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            $inMixColumn[$i][2] = $mixLeft[$i][2].$mixRight[$i][2];
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
                    $dataLeft[$i][$j] = substr($this->multiply0E($fourthState[$i][$j]),0,1);
                }
                if ($j == 1) {
                    $dataLeft[$i][$j] = substr($this->multiply0B($fourthState[$i][$j]),0,1);
                }
                if ($j == 2) {
                    $dataLeft[$i][$j] = substr($this->multiply0D($fourthState[$i][$j]),0,1);
                }
                if ($j == 3) {
                    $dataLeft[$i][$j] = substr($this->multiply09($fourthState[$i][$j]),0,1);
                }
            }
            $subLeft1[$i] = $this->XOR4($dataLeft[$i][0].$dataLeft[$i][1]);
            $subLeft2[$i] = $this->XOR4($dataLeft[$i][2].$dataLeft[$i][3]);
            $mixLeft[$i][3] = $this->XOR4($subLeft1[$i].$subLeft2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            for ($j=0; $j < 4; $j++) { 
                if ($j == 0) {
                    $dataRight[$i][$j] = substr($this->multiply0E($fourthState[$i][$j]),-1);
                }
                if ($j == 1) {
                    $dataRight[$i][$j] = substr($this->multiply0B($fourthState[$i][$j]),-1);
                }
                if ($j == 2) {
                    $dataRight[$i][$j] = substr($this->multiply0D($fourthState[$i][$j]),-1);
                }
                if ($j == 3) {
                    $dataRight[$i][$j] = substr($this->multiply09($fourthState[$i][$j]),-1);
                }
            }
            $subRight1[$i] = $this->XOR4($dataRight[$i][0].$dataRight[$i][1]);
            $subRight2[$i] = $this->XOR4($dataRight[$i][2].$dataRight[$i][3]);
            $mixRight[$i][3] = $this->XOR4($subRight1[$i].$subRight2[$i]);
        }

        for ($i=0; $i < 4; $i++) { 
            $inMixColumn[$i][3] = $mixLeft[$i][3].$mixRight[$i][3];
        }

        return $inMixColumn;
    }

    public function decryption(Request $request)
    {
        $ciphertext = $request->ciphertext;
        $cipherkey = $request->cipherkey;

        // validation plaintext
        if (!$request->ciphertext) {
            return response()->json([
                'success' => '0',
                'ciphertext' => 'ciphertext tidak ditemukan.'
            ]);
        } elseif (strlen($request->ciphertext) != 32) {
            return response()->json([
                'success' => '0',
                'ciphertext' => 'ciphertext hexadecimal harus 16 byte atau 16 karakter.'
            ]);
        }

        // validation cipkerkey
        if (!$request->cipherkey) {
            return response()->json([
                'success' => '0',
                'cipherkey' => 'cipherkey tidak ditemukan.'
            ]);
        } elseif (strlen($request->cipherkey) != 16) {
            return response()->json([
                'success' => '0',
                'cipherkey' => 'cipherkey harus 16 byte atau 16 karakter.'
            ]);
        }

        $ciphertextHex = $this->ciphertextToHex($ciphertext);
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
        $state = $this->addRoundKey($ciphertextHex, $keySchedule[9]);

        $state = $this->inShiftRows($state);
        
        $state = $this->inSubBytes($state);

        /**
         * Round 9 - 1
         */

        for ($i=8; $i >= 0; $i--) { 
            $state = $this->addRoundKey($state, $keySchedule[$i]);
    
            $state = $this->inMixColumn($state);

            $state = $this->inShiftRows($state);
        
            $state = $this->inSubBytes($state);
        }

        /**
         * Final Round
         */

        // AddRoundKey
        $state = $this->addRoundKey($state, $cipherkeyHex);

        $plaintext = $this->plaintext($state);

        return response()->json([
            'plaintext' => $plaintext
        ]);
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

    public function inSBox($key)
    {
        $invSBox = array(
            '00' => '52',
            '01' => '09',
            '02' => '6A',
            '03' => 'D5',
            '04' => '30',
            '05' => '36',
            '06' => 'A5',
            '07' => '38',
            '08' => 'BF',
            '09' => '40',
            '0A' => 'A3',
            '0B' => '9E',
            '0C' => '81',
            '0D' => 'F3',
            '0E' => 'D7',
            '0F' => 'FB',
            '10' => '7C',
            '11' => 'E3',
            '12' => '39',
            '13' => '82',
            '14' => '9B',
            '15' => '2F',
            '16' => 'FF',
            '17' => '87',
            '18' => '34',
            '19' => '8E',
            '1A' => '43',
            '1B' => '44',
            '1C' => 'C4',
            '1D' => 'DE',
            '1E' => 'E9',
            '1F' => 'CB',
            '20' => '54',
            '21' => '7B',
            '22' => '94',
            '23' => '32',
            '24' => 'A6',
            '25' => 'C2',
            '26' => '23',
            '27' => '3D',
            '28' => 'EE',
            '29' => '4C',
            '2A' => '95',
            '2B' => '0B',
            '2C' => '42',
            '2D' => 'FA',
            '2E' => 'C3',
            '2F' => '4E',
            '30' => '08',
            '31' => '2E',
            '32' => 'A1',
            '33' => '66',
            '34' => '28',
            '35' => 'D9',
            '36' => '24',
            '37' => 'B2',
            '38' => '76',
            '39' => '5B',
            '3A' => 'A2',
            '3B' => '49',
            '3C' => '6D',
            '3D' => '8B',
            '3E' => 'D1',
            '3F' => '25',
            '40' => '72',
            '41' => 'F8',
            '42' => 'F6',
            '43' => '64',
            '44' => '86',
            '45' => '68',
            '46' => '98',
            '47' => '16',
            '48' => 'D4',
            '49' => 'A4',
            '4A' => '5C',
            '4B' => 'CC',
            '4C' => '5D',
            '4D' => '65',
            '4E' => 'B6',
            '4F' => '92',
            '50' => '6C',
            '51' => '70',
            '52' => '48',
            '53' => '50',
            '54' => 'FD',
            '55' => 'ED',
            '56' => 'B9',
            '57' => 'DA',
            '58' => '5E',
            '59' => '15',
            '5A' => '46',
            '5B' => '57',
            '5C' => 'A7',
            '5D' => '8D',
            '5E' => '9D',
            '5F' => '84',
            '60' => '90',
            '61' => 'D8',
            '62' => 'AB',
            '63' => '00',
            '64' => '8C',
            '65' => 'BC',
            '66' => 'D3',
            '67' => '0A',
            '68' => 'F7',
            '69' => 'E4',
            '6A' => '58',
            '6B' => '05',
            '6C' => 'B8',
            '6D' => 'B3',
            '6E' => '45',
            '6F' => '06',
            '70' => 'D0',
            '71' => '2C',
            '72' => '1E',
            '73' => '8F',
            '74' => 'CA',
            '75' => '3F',
            '76' => '0F',
            '77' => '02',
            '78' => 'C1',
            '79' => 'AF',
            '7A' => 'BD',
            '7B' => '03',
            '7C' => '01',
            '7D' => '13',
            '7E' => '8A',
            '7F' => '6B',
            '80' => '3A',
            '81' => '91',
            '82' => '11',
            '83' => '41',
            '84' => '4F',
            '85' => '67',
            '86' => 'DC',
            '87' => 'EA',
            '88' => '97',
            '89' => 'F2',
            '8A' => 'CF',
            '8B' => 'CE',
            '8C' => 'F0',
            '8D' => 'B4',
            '8E' => 'E6',
            '8F' => '73',
            '90' => '96',
            '91' => 'AC',
            '92' => '74',
            '93' => '22',
            '94' => 'E7',
            '95' => 'AD',
            '96' => '35',
            '97' => '85',
            '98' => 'E2',
            '99' => 'F9',
            '9A' => '37',
            '9B' => 'E8',
            '9C' => '1C',
            '9D' => '75',
            '9E' => 'DF',
            '9F' => '6E',
            'A0' => '47',
            'A1' => 'F1',
            'A2' => '1A',
            'A3' => '71',
            'A4' => '1D',
            'A5' => '29',
            'A6' => 'C5',
            'A7' => '89',
            'A8' => '6F',
            'A9' => 'B7',
            'AA' => '62',
            'AB' => '0E',
            'AC' => 'AA',
            'AD' => '18',
            'AE' => 'BE',
            'AF' => '1B',
            'B0' => 'FC',
            'B1' => '56',
            'B2' => '3E',
            'B3' => '4B',
            'B4' => 'C6',
            'B5' => 'D2',
            'B6' => '79',
            'B7' => '20',
            'B8' => '9A',
            'B9' => 'DB',
            'BA' => 'C0',
            'BB' => 'FE',
            'BC' => '78',
            'BD' => 'CD',
            'BE' => '5A',
            'BF' => 'F4',
            'C0' => '1F',
            'C1' => 'DD',
            'C2' => 'A8',
            'C3' => '33',
            'C4' => '88',
            'C5' => '07',
            'C6' => 'C7',
            'C7' => '31',
            'C8' => 'B1',
            'C9' => '12',
            'CA' => '10',
            'CB' => '59',
            'CC' => '27',
            'CD' => '80',
            'CE' => 'EC',
            'CF' => '5F',
            'D0' => '60',
            'D1' => '51',
            'D2' => '7F',
            'D3' => 'A9',
            'D4' => '19',
            'D5' => 'B5',
            'D6' => '4A',
            'D7' => '0D',
            'D8' => '2D',
            'D9' => 'E5',
            'DA' => '7A',
            'DB' => '9F',
            'DC' => '93',
            'DD' => 'C9',
            'DE' => '9C',
            'DF' => 'EF',
            'E0' => 'A0',
            'E1' => 'E0',
            'E2' => '3B',
            'E3' => '4D',
            'E4' => 'AE',
            'E5' => '2A',
            'E6' => 'F5',
            'E7' => 'B0',
            'E8' => 'C8',
            'E9' => 'EB',
            'EA' => 'BB',
            'EB' => '3C',
            'EC' => '83',
            'ED' => '53',
            'EE' => '99',
            'EF' => '61',
            'F0' => '17',
            'F1' => '2B',
            'F2' => '04',
            'F3' => '7E',
            'F4' => 'BA',
            'F5' => '77',
            'F6' => 'D6',
            'F7' => '26',
            'F8' => 'E1',
            'F9' => '69',
            'FA' => '14',
            'FB' => '63',
            'FC' => '55',
            'FD' => '21',
            'FE' => '0C',
            'FF' => '7D'
        );
        
        return $invSBox[$key];
    }
    
    public function XOR4($key)
    {
        $xor4 = array(
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
        
        return $xor4[$key];
    }

    public function multiply09($key)
    {
        $multiply09 = array(
            '00' => '00',
            '01' => '09',
            '02' => '12',
            '03' => '1B',
            '04' => '24',
            '05' => '2D',
            '06' => '36',
            '07' => '3F',
            '08' => '48',
            '09' => '41',
            '0A' => '5A',
            '0B' => '53',
            '0C' => '6C',
            '0D' => '65',
            '0E' => '7E',
            '0F' => '77',
            '10' => '90',
            '11' => '99',
            '12' => '82',
            '13' => '8B',
            '14' => 'B4',
            '15' => 'BD',
            '16' => 'A6',
            '17' => 'AF',
            '18' => 'D8',
            '19' => 'D1',
            '1A' => 'CA',
            '1B' => 'C3',
            '1C' => 'FC',
            '1D' => 'F5',
            '1E' => 'EE',
            '1F' => 'E7',
            '20' => '3B',
            '21' => '32',
            '22' => '29',
            '23' => '20',
            '24' => '1F',
            '25' => '16',
            '26' => '0D',
            '27' => '04',
            '28' => '73',
            '29' => '7A',
            '2A' => '61',
            '2B' => '68',
            '2C' => '57',
            '2D' => '5E',
            '2E' => '45',
            '2F' => '4C',
            '30' => 'AB',
            '31' => 'A2',
            '32' => 'B9',
            '33' => 'B0',
            '34' => '8F',
            '35' => '86',
            '36' => '9D',
            '37' => '94',
            '38' => 'E3',
            '39' => 'EA',
            '3A' => 'F1',
            '3B' => 'F8',
            '3C' => 'C7',
            '3D' => 'CE',
            '3E' => 'D5',
            '3F' => 'DC',
            '40' => '76',
            '41' => '7F',
            '42' => '64',
            '43' => '6D',
            '44' => '52',
            '45' => '5B',
            '46' => '40',
            '47' => '49',
            '48' => '3E',
            '49' => '37',
            '4A' => '2C',
            '4B' => '25',
            '4C' => '1A',
            '4D' => '13',
            '4E' => '08',
            '4F' => '01',
            '50' => 'E6',
            '51' => 'EF',
            '52' => 'F4',
            '53' => 'FD',
            '54' => 'C2',
            '55' => 'CB',
            '56' => 'D0',
            '57' => 'D9',
            '58' => 'AE',
            '59' => 'A7',
            '5A' => 'BC',
            '5B' => 'B5',
            '5C' => '8A',
            '5D' => '83',
            '5E' => '98',
            '5F' => '91',
            '60' => '4D',
            '61' => '44',
            '62' => '5F',
            '63' => '56',
            '64' => '69',
            '65' => '60',
            '66' => '7B',
            '67' => '72',
            '68' => '05',
            '69' => '0C',
            '6A' => '17',
            '6B' => '1E',
            '6C' => '21',
            '6D' => '28',
            '6E' => '33',
            '6F' => '3A',
            '70' => 'DD',
            '71' => 'D4',
            '72' => 'CF',
            '73' => 'C6',
            '74' => 'F9',
            '75' => 'F0',
            '76' => 'EB',
            '77' => 'E2',
            '78' => '95',
            '79' => '9C',
            '7A' => '87',
            '7B' => '8E',
            '7C' => 'B1',
            '7D' => 'B8',
            '7E' => 'A3',
            '7F' => 'AA',
            '80' => 'EC',
            '81' => 'E5',
            '82' => 'FE',
            '83' => 'F7',
            '84' => 'C8',
            '85' => 'C1',
            '86' => 'DA',
            '87' => 'D3',
            '88' => 'A4',
            '89' => 'AD',
            '8A' => 'B6',
            '8B' => 'BF',
            '8C' => '80',
            '8D' => '89',
            '8E' => '92',
            '8F' => '9B',
            '90' => '7C',
            '91' => '75',
            '92' => '6E',
            '93' => '67',
            '94' => '58',
            '95' => '51',
            '96' => '4A',
            '97' => '43',
            '98' => '34',
            '99' => '3D',
            '9A' => '26',
            '9B' => '2F',
            '9C' => '10',
            '9D' => '19',
            '9E' => '02',
            '9F' => '0B',
            'A0' => 'D7',
            'A1' => 'DE',
            'A2' => 'C5',
            'A3' => 'CC',
            'A4' => 'F3',
            'A5' => 'FA',
            'A6' => 'E1',
            'A7' => 'E8',
            'A8' => '9F',
            'A9' => '96',
            'AA' => '8D',
            'AB' => '84',
            'AC' => 'BB',
            'AD' => 'B2',
            'AE' => 'A9',
            'AF' => 'A0',
            'B0' => '47',
            'B1' => '4E',
            'B2' => '55',
            'B3' => '5C',
            'B4' => '63',
            'B5' => '6A',
            'B6' => '71',
            'B7' => '78',
            'B8' => '0F',
            'B9' => '06',
            'BA' => '1D',
            'BB' => '14',
            'BC' => '2B',
            'BD' => '22',
            'BE' => '39',
            'BF' => '30',
            'C0' => '9A',
            'C1' => '93',
            'C2' => '88',
            'C3' => '81',
            'C4' => 'BE',
            'C5' => 'B7',
            'C6' => 'AC',
            'C7' => 'A5',
            'C8' => 'D2',
            'C9' => 'DB',
            'CA' => 'C0',
            'CB' => 'C9',
            'CC' => 'F6',
            'CD' => 'FF',
            'CE' => 'E4',
            'CF' => 'ED',
            'D0' => '0A',
            'D1' => '03',
            'D2' => '18',
            'D3' => '11',
            'D4' => '2E',
            'D5' => '27',
            'D6' => '3C',
            'D7' => '35',
            'D8' => '42',
            'D9' => '4B',
            'DA' => '50',
            'DB' => '59',
            'DC' => '66',
            'DD' => '6F',
            'DE' => '74',
            'DF' => '7D',
            'E0' => 'A1',
            'E1' => 'A8',
            'E2' => 'B3',
            'E3' => 'BA',
            'E4' => '85',
            'E5' => '8C',
            'E6' => '97',
            'E7' => '9E',
            'E8' => 'E9',
            'E9' => 'E0',
            'EA' => 'FB',
            'EB' => 'F2',
            'EC' => 'CD',
            'ED' => 'C4',
            'EE' => 'DF',
            'EF' => 'D6',
            'F0' => '31',
            'F1' => '38',
            'F2' => '23',
            'F3' => '2A',
            'F4' => '15',
            'F5' => '1C',
            'F6' => '07',
            'F7' => '0E',
            'F8' => '79',
            'F9' => '70',
            'FA' => '6B',
            'FB' => '62',
            'FC' => '5D',
            'FD' => '54',
            'FE' => '4F',
            'FF' => '46'
        );
        
        return $multiply09[$key];
    }

    public function multiply0B($key)
    {
        $multiply0B = array(
            '00' => '00',
            '01' => '0B',
            '02' => '16',
            '03' => '1D',
            '04' => '2C',
            '05' => '27',
            '06' => '3A',
            '07' => '31',
            '08' => '58',
            '09' => '53',
            '0A' => '4E',
            '0B' => '45',
            '0C' => '74',
            '0D' => '7F',
            '0E' => '62',
            '0F' => '69',
            '10' => 'B0',
            '11' => 'BB',
            '12' => 'A6',
            '13' => 'AD',
            '14' => '9C',
            '15' => '97',
            '16' => '8A',
            '17' => '81',
            '18' => 'E8',
            '19' => 'E3',
            '1A' => 'FE',
            '1B' => 'F5',
            '1C' => 'C4',
            '1D' => 'CF',
            '1E' => 'D2',
            '1F' => 'D9',
            '20' => '7B',
            '21' => '70',
            '22' => '6D',
            '23' => '66',
            '24' => '57',
            '25' => '5C',
            '26' => '41',
            '27' => '4A',
            '28' => '23',
            '29' => '28',
            '2A' => '35',
            '2B' => '3E',
            '2C' => '0F',
            '2D' => '04',
            '2E' => '19',
            '2F' => '12',
            '30' => 'CB',
            '31' => 'C0',
            '32' => 'DD',
            '33' => 'D6',
            '34' => 'E7',
            '35' => 'EC',
            '36' => 'F1',
            '37' => 'FA',
            '38' => '93',
            '39' => '98',
            '3A' => '85',
            '3B' => '8E',
            '3C' => 'BF',
            '3D' => 'B4',
            '3E' => 'A9',
            '3F' => 'A2',
            '40' => 'F6',
            '41' => 'FD',
            '42' => 'E0',
            '43' => 'EB',
            '44' => 'DA',
            '45' => 'D1',
            '46' => 'CC',
            '47' => 'C7',
            '48' => 'AE',
            '49' => 'A5',
            '4A' => 'B8',
            '4B' => 'B3',
            '4C' => '82',
            '4D' => '89',
            '4E' => '94',
            '4F' => '9F',
            '50' => '46',
            '51' => '4D',
            '52' => '50',
            '53' => '5B',
            '54' => '6A',
            '55' => '61',
            '56' => '7C',
            '57' => '77',
            '58' => '1E',
            '59' => '15',
            '5A' => '08',
            '5B' => '03',
            '5C' => '32',
            '5D' => '39',
            '5E' => '24',
            '5F' => '2F',
            '60' => '8D',
            '61' => '86',
            '62' => '9B',
            '63' => '90',
            '64' => 'A1',
            '65' => 'AA',
            '66' => 'B7',
            '67' => 'BC',
            '68' => 'D5',
            '69' => 'DE',
            '6A' => 'C3',
            '6B' => 'C8',
            '6C' => 'F9',
            '6D' => 'F2',
            '6E' => 'EF',
            '6F' => 'E4',
            '70' => '3D',
            '71' => '36',
            '72' => '2B',
            '73' => '20',
            '74' => '11',
            '75' => '1A',
            '76' => '07',
            '77' => '0C',
            '78' => '65',
            '79' => '6E',
            '7A' => '73',
            '7B' => '78',
            '7C' => '49',
            '7D' => '42',
            '7E' => '5F',
            '7F' => '54',
            '80' => 'F7',
            '81' => 'FC',
            '82' => 'E1',
            '83' => 'EA',
            '84' => 'DB',
            '85' => 'D0',
            '86' => 'CD',
            '87' => 'C6',
            '88' => 'AF',
            '89' => 'A4',
            '8A' => 'B9',
            '8B' => 'B2',
            '8C' => '83',
            '8D' => '88',
            '8E' => '95',
            '8F' => '9E',
            '90' => '47',
            '91' => '4C',
            '92' => '51',
            '93' => '5A',
            '94' => '6B',
            '95' => '60',
            '96' => '7D',
            '97' => '76',
            '98' => '1F',
            '99' => '14',
            '9A' => '09',
            '9B' => '02',
            '9C' => '33',
            '9D' => '38',
            '9E' => '25',
            '9F' => '2E',
            'A0' => '8C',
            'A1' => '87',
            'A2' => '9A',
            'A3' => '91',
            'A4' => 'A0',
            'A5' => 'AB',
            'A6' => 'B6',
            'A7' => 'BD',
            'A8' => 'D4',
            'A9' => 'DF',
            'AA' => 'C2',
            'AB' => 'C9',
            'AC' => 'F8',
            'AD' => 'F3',
            'AE' => 'EE',
            'AF' => 'E5',
            'B0' => '3C',
            'B1' => '37',
            'B2' => '2A',
            'B3' => '21',
            'B4' => '10',
            'B5' => '1B',
            'B6' => '06',
            'B7' => '0D',
            'B8' => '64',
            'B9' => '6F',
            'BA' => '72',
            'BB' => '79',
            'BC' => '48',
            'BD' => '43',
            'BE' => '5E',
            'BF' => '55',
            'C0' => '01',
            'C1' => '0A',
            'C2' => '17',
            'C3' => '1C',
            'C4' => '2D',
            'C5' => '26',
            'C6' => '3B',
            'C7' => '30',
            'C8' => '59',
            'C9' => '52',
            'CA' => '4F',
            'CB' => '44',
            'CC' => '75',
            'CD' => '7E',
            'CE' => '63',
            'CF' => '68',
            'D0' => 'B1',
            'D1' => 'BA',
            'D2' => 'A7',
            'D3' => 'AC',
            'D4' => '9D',
            'D5' => '96',
            'D6' => '8B',
            'D7' => '80',
            'D8' => 'E9',
            'D9' => 'E2',
            'DA' => 'FF',
            'DB' => 'F4',
            'DC' => 'C5',
            'DD' => 'CE',
            'DE' => 'D3',
            'DF' => 'D8',
            'E0' => '7A',
            'E1' => '71',
            'E2' => '6C',
            'E3' => '67',
            'E4' => '56',
            'E5' => '5D',
            'E6' => '40',
            'E7' => '4B',
            'E8' => '22',
            'E9' => '29',
            'EA' => '34',
            'EB' => '3F',
            'EC' => '0E',
            'ED' => '05',
            'EE' => '18',
            'EF' => '13',
            'F0' => 'CA',
            'F1' => 'C1',
            'F2' => 'DC',
            'F3' => 'D7',
            'F4' => 'E6',
            'F5' => 'ED',
            'F6' => 'F0',
            'F7' => 'FB',
            'F8' => '92',
            'F9' => '99',
            'FA' => '84',
            'FB' => '8F',
            'FC' => 'BE',
            'FD' => 'B5',
            'FE' => 'A8',
            'FF' => 'A3'
        );
        
        return $multiply0B[$key];
    }

    public function multiply0D($key)
    {
        $multiply0D = array(
            '00' => '00',
            '01' => '0D',
            '02' => '1A',
            '03' => '17',
            '04' => '34',
            '05' => '39',
            '06' => '2E',
            '07' => '23',
            '08' => '68',
            '09' => '65',
            '0A' => '72',
            '0B' => '7F',
            '0C' => '5C',
            '0D' => '51',
            '0E' => '46',
            '0F' => '4B',
            '10' => 'D0',
            '11' => 'DD',
            '12' => 'CA',
            '13' => 'C7',
            '14' => 'E4',
            '15' => 'E9',
            '16' => 'FE',
            '17' => 'F3',
            '18' => 'B8',
            '19' => 'B5',
            '1A' => 'A2',
            '1B' => 'AF',
            '1C' => '8C',
            '1D' => '81',
            '1E' => '96',
            '1F' => '9B',
            '20' => 'BB',
            '21' => 'B6',
            '22' => 'A1',
            '23' => 'AC',
            '24' => '8F',
            '25' => '82',
            '26' => '95',
            '27' => '98',
            '28' => 'D3',
            '29' => 'DE',
            '2A' => 'C9',
            '2B' => 'C4',
            '2C' => 'E7',
            '2D' => 'EA',
            '2E' => 'FD',
            '2F' => 'F0',
            '30' => '6B',
            '31' => '66',
            '32' => '71',
            '33' => '7C',
            '34' => '5F',
            '35' => '52',
            '36' => '45',
            '37' => '48',
            '38' => '03',
            '39' => '0E',
            '3A' => '19',
            '3B' => '14',
            '3C' => '37',
            '3D' => '3A',
            '3E' => '2D',
            '3F' => '20',
            '40' => '6D',
            '41' => '60',
            '42' => '77',
            '43' => '7A',
            '44' => '59',
            '45' => '54',
            '46' => '43',
            '47' => '4E',
            '48' => '05',
            '49' => '08',
            '4A' => '1F',
            '4B' => '12',
            '4C' => '31',
            '4D' => '3C',
            '4E' => '2B',
            '4F' => '26',
            '50' => 'BD',
            '51' => 'B0',
            '52' => 'A7',
            '53' => 'AA',
            '54' => '89',
            '55' => '84',
            '56' => '93',
            '57' => '9E',
            '58' => 'D5',
            '59' => 'D8',
            '5A' => 'CF',
            '5B' => 'C2',
            '5C' => 'E1',
            '5D' => 'EC',
            '5E' => 'FB',
            '5F' => 'F6',
            '60' => 'D6',
            '61' => 'DB',
            '62' => 'CC',
            '63' => 'C1',
            '64' => 'E2',
            '65' => 'EF',
            '66' => 'F8',
            '67' => 'F5',
            '68' => 'BE',
            '69' => 'B3',
            '6A' => 'A4',
            '6B' => 'A9',
            '6C' => '8A',
            '6D' => '87',
            '6E' => '90',
            '6F' => '9D',
            '70' => '06',
            '71' => '0B',
            '72' => '1C',
            '73' => '11',
            '74' => '32',
            '75' => '3F',
            '76' => '28',
            '77' => '25',
            '78' => '6E',
            '79' => '63',
            '7A' => '74',
            '7B' => '79',
            '7C' => '5A',
            '7D' => '57',
            '7E' => '40',
            '7F' => '4D',
            '80' => 'DA',
            '81' => 'D7',
            '82' => 'C0',
            '83' => 'CD',
            '84' => 'EE',
            '85' => 'E3',
            '86' => 'F4',
            '87' => 'F9',
            '88' => 'B2',
            '89' => 'BF',
            '8A' => 'A8',
            '8B' => 'A5',
            '8C' => '86',
            '8D' => '8B',
            '8E' => '9C',
            '8F' => '91',
            '90' => '0A',
            '91' => '07',
            '92' => '10',
            '93' => '1D',
            '94' => '3E',
            '95' => '33',
            '96' => '24',
            '97' => '29',
            '98' => '62',
            '99' => '6F',
            '9A' => '78',
            '9B' => '75',
            '9C' => '56',
            '9D' => '5B',
            '9E' => '4C',
            '9F' => '41',
            'A0' => '61',
            'A1' => '6C',
            'A2' => '7B',
            'A3' => '76',
            'A4' => '55',
            'A5' => '58',
            'A6' => '4F',
            'A7' => '42',
            'A8' => '09',
            'A9' => '04',
            'AA' => '13',
            'AB' => '1E',
            'AC' => '3D',
            'AD' => '30',
            'AE' => '27',
            'AF' => '2A',
            'B0' => 'B1',
            'B1' => 'BC',
            'B2' => 'AB',
            'B3' => 'A6',
            'B4' => '85',
            'B5' => '88',
            'B6' => '9F',
            'B7' => '92',
            'B8' => 'D9',
            'B9' => 'D4',
            'BA' => 'C3',
            'BB' => 'CE',
            'BC' => 'ED',
            'BD' => 'E0',
            'BE' => 'F7',
            'BF' => 'FA',
            'C0' => 'B7',
            'C1' => 'BA',
            'C2' => 'AD',
            'C3' => 'A0',
            'C4' => '83',
            'C5' => '8E',
            'C6' => '99',
            'C7' => '94',
            'C8' => 'DF',
            'C9' => 'D2',
            'CA' => 'C5',
            'CB' => 'C8',
            'CC' => 'EB',
            'CD' => 'E6',
            'CE' => 'F1',
            'CF' => 'FC',
            'D0' => '67',
            'D1' => '6A',
            'D2' => '7D',
            'D3' => '70',
            'D4' => '53',
            'D5' => '5E',
            'D6' => '49',
            'D7' => '44',
            'D8' => '0F',
            'D9' => '02',
            'DA' => '15',
            'DB' => '18',
            'DC' => '3B',
            'DD' => '36',
            'DE' => '21',
            'DF' => '2C',
            'E0' => '0C',
            'E1' => '01',
            'E2' => '16',
            'E3' => '1B',
            'E4' => '38',
            'E5' => '35',
            'E6' => '22',
            'E7' => '2F',
            'E8' => '64',
            'E9' => '69',
            'EA' => '7E',
            'EB' => '73',
            'EC' => '50',
            'ED' => '5D',
            'EE' => '4A',
            'EF' => '47',
            'F0' => 'DC',
            'F1' => 'D1',
            'F2' => 'C6',
            'F3' => 'CB',
            'F4' => 'E8',
            'F5' => 'E5',
            'F6' => 'F2',
            'F7' => 'FF',
            'F8' => 'B4',
            'F9' => 'B9',
            'FA' => 'AE',
            'FB' => 'A3',
            'FC' => '80',
            'FD' => '8D',
            'FE' => '9A',
            'FF' => '97'
        );
        
        return $multiply0D[$key];
    }

    public function multiply0E($key)
    {
        $multiply0E = array(
            '00' => '00',
            '01' => '0E',
            '02' => '1C',
            '03' => '12',
            '04' => '38',
            '05' => '36',
            '06' => '24',
            '07' => '2A',
            '08' => '70',
            '09' => '7E',
            '0A' => '6C',
            '0B' => '62',
            '0C' => '48',
            '0D' => '46',
            '0E' => '54',
            '0F' => '5A',
            '10' => 'E0',
            '11' => 'EE',
            '12' => 'FC',
            '13' => 'F2',
            '14' => 'D8',
            '15' => 'D6',
            '16' => 'C4',
            '17' => 'CA',
            '18' => '90',
            '19' => '9E',
            '1A' => '8C',
            '1B' => '82',
            '1C' => 'A8',
            '1D' => 'A6',
            '1E' => 'B4',
            '1F' => 'BA',
            '20' => 'DB',
            '21' => 'D5',
            '22' => 'C7',
            '23' => 'C9',
            '24' => 'E3',
            '25' => 'ED',
            '26' => 'FF',
            '27' => 'F1',
            '28' => 'AB',
            '29' => 'A5',
            '2A' => 'B7',
            '2B' => 'B9',
            '2C' => '93',
            '2D' => '9D',
            '2E' => '8F',
            '2F' => '81',
            '30' => '3B',
            '31' => '35',
            '32' => '27',
            '33' => '29',
            '34' => '03',
            '35' => '0D',
            '36' => '1F',
            '37' => '11',
            '38' => '4B',
            '39' => '45',
            '3A' => '57',
            '3B' => '59',
            '3C' => '73',
            '3D' => '7D',
            '3E' => '6F',
            '3F' => '61',
            '40' => 'AD',
            '41' => 'A3',
            '42' => 'B1',
            '43' => 'BF',
            '44' => '95',
            '45' => '9B',
            '46' => '89',
            '47' => '87',
            '48' => 'DD',
            '49' => 'D3',
            '4A' => 'C1',
            '4B' => 'CF',
            '4C' => 'E5',
            '4D' => 'EB',
            '4E' => 'F9',
            '4F' => 'F7',
            '50' => '4D',
            '51' => '43',
            '52' => '51',
            '53' => '5F',
            '54' => '75',
            '55' => '7B',
            '56' => '69',
            '57' => '67',
            '58' => '3D',
            '59' => '33',
            '5A' => '21',
            '5B' => '2F',
            '5C' => '05',
            '5D' => '0B',
            '5E' => '19',
            '5F' => '17',
            '60' => '76',
            '61' => '78',
            '62' => '6A',
            '63' => '64',
            '64' => '4E',
            '65' => '40',
            '66' => '52',
            '67' => '5C',
            '68' => '06',
            '69' => '08',
            '6A' => '1A',
            '6B' => '14',
            '6C' => '3E',
            '6D' => '30',
            '6E' => '22',
            '6F' => '2C',
            '70' => '96',
            '71' => '98',
            '72' => '8A',
            '73' => '84',
            '74' => 'AE',
            '75' => 'A0',
            '76' => 'B2',
            '77' => 'BC',
            '78' => 'E6',
            '79' => 'E8',
            '7A' => 'FA',
            '7B' => 'F4',
            '7C' => 'DE',
            '7D' => 'D0',
            '7E' => 'C2',
            '7F' => 'CC',
            '80' => '41',
            '81' => '4F',
            '82' => '5D',
            '83' => '53',
            '84' => '79',
            '85' => '77',
            '86' => '65',
            '87' => '6B',
            '88' => '31',
            '89' => '3F',
            '8A' => '2D',
            '8B' => '23',
            '8C' => '09',
            '8D' => '07',
            '8E' => '15',
            '8F' => '1B',
            '90' => 'A1',
            '91' => 'AF',
            '92' => 'BD',
            '93' => 'B3',
            '94' => '99',
            '95' => '97',
            '96' => '85',
            '97' => '8B',
            '98' => 'D1',
            '99' => 'DF',
            '9A' => 'CD',
            '9B' => 'C3',
            '9C' => 'E9',
            '9D' => 'E7',
            '9E' => 'F5',
            '9F' => 'FB',
            'A0' => '9A',
            'A1' => '94',
            'A2' => '86',
            'A3' => '88',
            'A4' => 'A2',
            'A5' => 'AC',
            'A6' => 'BE',
            'A7' => 'B0',
            'A8' => 'EA',
            'A9' => 'E4',
            'AA' => 'F6',
            'AB' => 'F8',
            'AC' => 'D2',
            'AD' => 'DC',
            'AE' => 'CE',
            'AF' => 'C0',
            'B0' => '7A',
            'B1' => '74',
            'B2' => '66',
            'B3' => '68',
            'B4' => '42',
            'B5' => '4C',
            'B6' => '5E',
            'B7' => '50',
            'B8' => '0A',
            'B9' => '04',
            'BA' => '16',
            'BB' => '18',
            'BC' => '32',
            'BD' => '3C',
            'BE' => '2E',
            'BF' => '20',
            'C0' => 'EC',
            'C1' => 'E2',
            'C2' => 'F0',
            'C3' => 'FE',
            'C4' => 'D4',
            'C5' => 'DA',
            'C6' => 'C8',
            'C7' => 'C6',
            'C8' => '9C',
            'C9' => '92',
            'CA' => '80',
            'CB' => '8E',
            'CC' => 'A4',
            'CD' => 'AA',
            'CE' => 'B8',
            'CF' => 'B6',
            'D0' => '0C',
            'D1' => '02',
            'D2' => '10',
            'D3' => '1E',
            'D4' => '34',
            'D5' => '3A',
            'D6' => '28',
            'D7' => '26',
            'D8' => '7C',
            'D9' => '72',
            'DA' => '60',
            'DB' => '6E',
            'DC' => '44',
            'DD' => '4A',
            'DE' => '58',
            'DF' => '56',
            'E0' => '37',
            'E1' => '39',
            'E2' => '2B',
            'E3' => '25',
            'E4' => '0F',
            'E5' => '01',
            'E6' => '13',
            'E7' => '1D',
            'E8' => '47',
            'E9' => '49',
            'EA' => '5B',
            'EB' => '55',
            'EC' => '7F',
            'ED' => '71',
            'EE' => '63',
            'EF' => '6D',
            'F0' => 'D7',
            'F1' => 'D9',
            'F2' => 'CB',
            'F3' => 'C5',
            'F4' => 'EF',
            'F5' => 'E1',
            'F6' => 'F3',
            'F7' => 'FD',
            'F8' => 'A7',
            'F9' => 'A9',
            'FA' => 'BB',
            'FB' => 'B5',
            'FC' => '9F',
            'FD' => '91',
            'FE' => '83',
            'FF' => '8D'
        );
        
        return $multiply0E[$key];
    }
}
