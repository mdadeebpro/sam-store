<?php

namespace ArPHP\I18N;

class Arabic
{
    /** @var string */
    public $version = '7.0.0';

    /** @var \Psr\SimpleCache\CacheInterface|null */
    private $___psr16 = null;

    /** @var int|null Default TTL seconds for cached entries (null = no expiry)
*/
    private $___psr16Ttl = 86400;

    /** @var array<> in-process cache for data files */
    private $___resCache = array();

    /** @var array<string> */
    private $arStandardPatterns = [];

    /** @var array<string> */
    private $arStandardReplacements = [];

    /** @var array<string> */
    private $arFemaleNames = [];

    /** @var array<string> */
    private $arMaleNames = [];

    /** @var array<string> */
    private $strToTimeSearch = [];

    /** @var array<string> */
    private $strToTimeReplace = [];

    /** @var array<string> */
    private $hj = [];

    /** @var array<string> */
    private $strToTimePatterns = [];

    /** @var array<string> */
    private $strToTimeReplacements = [];

    /** @var array<string> */
    private $umAlqoura;

    /** @var array<string> */
    private $arFinePatterns = ["/'+/u", "/([\- ])'/u", '/(.)#/u'];

    /** @var array<string> */
    private $arFineReplacements = ["'", '\\1', "\\1'\\1"];

    /** @var array<string> */
    private $diariticalSearch = [];

    /** @var array<string> */
    private $diariticalReplace = [];

    /** @var array<string> */
    private $en2arPregSearch = [];

    /** @var array<string> */
    private $en2arPregReplace = [];

    /** @var array<string> */
    private $en2arStrSearch = [];

    /** @var array<string> */
    private $en2arStrReplace = [];

    /** @var array<string> */
    private $ar2enPregSearch = [];

    /** @var array<string> */
    private $ar2enPregReplace = [];

    /** @var array<string> */
    private $ar2enStrSearch = [];

    /** @var array<string> */
    private $ar2enStrReplace = [];

    /** @var array<string> */
    private $iso233Search = [];

    /** @var array<string> */
    private $iso233Replace = [];

    /** @var array<string> */
    private $rjgcSearch = [];

    /** @var array<string> */
    private $rjgcReplace = [];

    /** @var array<string> */
    private $sesSearch = [];

    /** @var array<string> */
    private $sesReplace = [];

    /** @var int */
    private $arDateMode = 1;

    /** @var array<array<string|array<string>>> */
    private $arDateJSON = [];

    /** @var array<string|array<string|array<string>>> */
    private $arNumberIndividual = [];

    /** @var array<array<string>> */
    private $arNumberComplications = [];

    /** @var array<string> */
    private $arNumberArabicIndic = [];

    /** @var array<string> */
    private $arNumberOrdering = [];

    /** @var array<array<string|array<string>>> */
    private $arNumberCurrency = [];

    /** @var array<int> */
    private $arNumberSpell = [];

    /** @var int */
    private $arNumberFeminine = 1;

    /** @var int */
    private $arNumberFormat = 1;

    /** @var int */
    private $arNumberOrder = 1;

    /** @var array<array<string>> */
    private $arLogodd;

    /** @var array<array<string>> */
    private $enLogodd;

    /** @var array<string> */
    private $arKeyboard = [];

    /** @var array<string> */
    private $enKeyboard = [];

    /** @var array<string> */
    private $frKeyboard = [];

    /** @var array<string> */
    private $soundexTransliteration = [];

    /** @var array<string> */
    private $soundexMap = [];

    /** @var array<string> */
    private $arSoundexCode = [];

    /** @var array<string> */
    private $arPhonixCode = [];

    /** @var int */
    private $soundexLen = 4;

    /** @var string */
    private $soundexLang = 'en';

    /** @var string */
    private $soundexCode = 'soundex';

    /** @var array<array<string>> */
    private $arGlyphs = null;

    /** @var null|string */
    private $arGlyphsVowel = null;

    /** @var array<string> */
    private $arQueryFields = [];

    /** @var array<string> */
    private $arQueryLexPatterns = [];

    /** @var array<string> */
    private $arQueryLexReplacements = [];

    /** @var int */
    private $arQueryMode = 0;

    /** @var int */
    private $salatYear = 1975;

    /** @var int */
    private $salatMonth = 8;

    /** @var int */
    private $salatDay = 2;

    /** @var int */
    private $salatZone = 2;

    /** @var float */
    private $salatLong = 37.15861;

    /** @var float */
    private $salatLat = 36.20278;

    /** @var int */
    private $salatElevation = 0;

    /** @var float */
    private $salatAB2 = -0.833333;

    /** @var float */
    private $salatAG2 = -18;

    /** @var float */
    private $salatAJ2 = -18;

    /** @var string */
    private $salatSchool = 'Shafi';

    /** @var string */
    private $salatView   = 'Sunni';

    /** @var array<string> */
    private $arNormalizeAlef = ['أ','إ','آ'];

    /** @var array<string> */
    private $arNormalizeDiacritics = ['َ','ً','ُ','ٌ','ِ','ٍ','ْ','ّ'];

    /** @var array<string> */
    private $arSeparators = ['.',"\n",'،','؛','(','[','{',')',']','}',',',';'];

    /** @var array<string> */
    private $arCommonChars = ['ة','ه','ي','ن','و','ت','ل','ا','س','م',
                              'e', 't', 'a', 'o', 'i', 'n', 's'];

    /** @var array<string> */
    private $arSummaryCommonWords = [];

    /** @var array<string> */
    private $arSummaryImportantWords = [];

    /** @var array<string> */
    private $arPluralsForms = [];

    /** @var array<string> */
    private $logOdd = [];

    /** @var array<string> */
    private $logOddStem = [];

    /** @var array<string> */
    private $allStems = [];

    /** @var string */
    private $rootDirectory;

    /** @var boolean */
    private $stripTatweel = true;

    /** @var boolean */
    private $stripTanween = true;

    /** @var boolean */
    private $stripShadda = true;

    /** @var boolean */
    private $stripLastHarakat = true;

    /** @var boolean */
    private $stripWordHarakat = true;

    /** @var boolean */
    private $normaliseLamAlef = true;

    /** @var boolean */
    private $normaliseAlef = true;

    /** @var boolean */
    private $normaliseHamza = true;

    /** @var boolean */
    private $normaliseTaa = true;

    /** @var array<string> */
    private $numeralHindu = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    /** @var array<string> */
    private $numeralPersian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']
;

    /** @var array<string> */
    private $numeralArabic = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /** @var object */
    private $speller = null;

    /** @var array<string> */
    private $dialectsStems = [];

    /** @var array<string> */
    private $logOddDialects = [];

    /** @var array<string> */
    private $logOddEgyptian = [];

    /** @var array<string> */
    private $logOddLevantine = [];

    /** @var array<string> */
    private $logOddMaghrebi = [];

    /** @var array<string> */
    private $logOddPeninsular = [];

    /** @var array<string> */
    private $arKeyX = [];

    /** @var array<string> */
    private $arKeyY = [];

    /** @var array<string> */
    private $arKeyZ = [];

    /** @var array<string> */
    private $arGraphGroup = [];

    /** @var array<string> */
    private $arSoundGroup = [];

    /** @var array<string> */
    private $arGapPenalty = [];

    /** @var float */
    private $keyboardWeight = 1;

    /** @var float */
    private $graphicWeight = 1;

    /** @var float */
    private $phoneticWeight = 1;

    // Lazy loading flags

    /** @var boolean */
    private $arStrToTimeLoad = false;

    /** @var boolean */
    private $arTransliterateLoad = false;

    /** @var boolean */
    private $arNumbersLoad = false;

    /** @var boolean */
    private $arKeySwapLoad = false;

    /** @var boolean */
    private $arSoundexLoad = false;

    /** @var boolean */
    private $arGlyphsLoad = false;

    /** @var boolean */
    private $arQueryLoad = false;

    /** @var boolean */
    private $arSummaryLoad = false;

    /** @var boolean */
    private $arSentimentLoad = false;

    /** @var boolean */
    private $arSpellerLoad = false;

    /** @var boolean */
    private $arDialectLoad = false;

    /** @var boolean */
    private $arSimilarityLoad = false;

    /** @var boolean */
    private $arNamesLoad = false;

    /** @var boolean */
    private $arDateLoad = false;

    /** @var boolean */
    private $arPluralLoad = false;

    public function __construct()
    {
        // require_once 'SarahSpell.php';

        mb_internal_encoding('UTF-8');

        $this->rootDirectory = dirname(__FILE__);

        $this->arStandardInit();
    }
    private function ___json($relativePath)
    {
        $path = __DIR__ . '/data/' . $relativePath;
        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    /** @return void */
    private function arGlyphsInit()
    {
        if ($this->arGlyphsLoad === false) {
            $this->arGlyphsVowel     = 'ًٌٍَُِّْ';
            $this->arGlyphs = $this->___json('ar_glyphs.json');
            $this->arGlyphsLoad = true;
        }
    }

    /**
     * Convert Arabic string into glyph joining in UTF-8 hexadecimals stream
     *
     * @param string $str Arabic string in UTF-8 charset
     *
     * @return string Arabic glyph joining in UTF-8 hexadecimals stream
     * @author Khaled Al-Sham'aa <khaled@ar-php.org>
     */
    private function arGlyphsPreConvert($str)
    {
        $this->arGlyphsInit();

        $crntChar = null;
        $prevChar = null;
        $nextChar = null;
        $output   = '';
        $number   = '';
        $chars    = [];

        $open_range  = ')]>}';
        $close_range = '([<{';

        $_temp = mb_strlen($str);

        // split the given string to an array of chars
        for ($i = 0; $i < $_temp; $i++) {
            $chars[] = mb_substr($str, $i, 1);
        }

        $max = count($chars);

        for ($i = $max - 1; $i >= 0; $i--) {
            $crntChar = $chars[$i];
            $form = 0;

            if ($i > 0) {
                $prevChar = $chars[$i - 1];
                if (mb_strpos($this->arGlyphsVowel, $prevChar) !== false && $i >
 1) {
                    $prevChar = $chars[$i - 2];

                    if (mb_strpos($this->arGlyphsVowel, $prevChar) !== false &&
$i > 2) {
                        $prevChar = $chars[$i - 3];
                    }
                }
            } else {
                $prevChar = ' ';
            }

            if (is_numeric($crntChar)) {
                $number = $crntChar . $number;
                continue;
            } elseif (strlen($number) > 0) {
                $output .= $number;
                $number  = '';
            }

            if (mb_strpos($open_range . $close_range, $crntChar) !== false) {
                $output .= ($close_range . $open_range)[mb_strpos($open_range .
$close_range, $crntChar)];
                continue;
            }

             if (ord($crntChar) < 128) {
                $output  .= $crntChar;
                $nextChar = $crntChar;
                continue;
            }
            if (
                $crntChar == 'ل' && isset($nextChar)
                && (mb_strpos('آأإا', $nextChar) !== false)
            ) {
                $output = substr($output, 0, strlen($output) - 8);
                if (isset($this->arGlyphs[$prevChar]['prevLink']) && $this->arGl
yphs[$prevChar]['prevLink'] == true) {
                    $output .= '&#x' . $this->arGlyphs[$crntChar . $nextChar][1]
 . ';';
                } else {
                    $output .= '&#x' . $this->arGlyphs[$crntChar . $nextChar][0]
 . ';';
                }
                if ($prevChar == 'ل') {
                    if (isset($chars[$i - 2])) {
                        $tmp_form = (isset($this->arGlyphs[$chars[$i - 2]]['prev
Link']) &&
                                     $this->arGlyphs[$chars[$i - 2]]['prevLink']
 == true) ? 3 : 2;
                    } else {
                        $tmp_form = 2;
                    }
                    $output .= '&#x' . $this->arGlyphs[$prevChar][$tmp_form] . '
;';
                    $i--;
                }
                continue;
            }

            if (mb_strpos($this->arGlyphsVowel, $crntChar) !== false) {

                if (mb_strpos($this->arGlyphsVowel, $chars[$i - 1]) !== false) {
                }
                switch ($crntChar) {
                    case 'ً':
                        $output .= '&#x064B;';
                        break;
                    case 'ٌ':
                        $output .= '&#x064C;';
                        break;
                    case 'ٍ':
                        $output .= '&#x064D;';
                        break;
                    case 'َ':
                        $output .= '&#x064E;';
                        break;
                    case 'ُ':
                        $output .= '&#x064F;';
                        break;
                    case 'ِ':
                        $output .= '&#x0650;';
                        break;
                    case 'ّ':
                        $output .= '&#x0651;';
                        break;
                    case 'ْ':
                        $output .= '&#x0652;';
                        break;
                }

                continue;
            }

            if ($prevChar && isset($this->arGlyphs[$prevChar]) && $this->arGlyph
s[$prevChar]['prevLink'] == true) {
                $form++;
            }

            if ($nextChar && isset($this->arGlyphs[$nextChar]) && $this->arGlyph
s[$nextChar]['nextLink'] == true) {
                $form += 2;
            }

            if (isset($this->arGlyphs[$crntChar])) {
                $output .= '&#x' . $this->arGlyphs[$crntChar][$form] . ';';
            } else {
                $output .= $crntChar;
            }

            $nextChar = $crntChar;
        }

        $output = $this->arGlyphsDecodeEntities($output, $exclude = ['&']);

        return $output;
    }

    public function utf8Glyphs($text, $max_chars = 50, $hindo = true, $forcertl
= false)
    {
        $this->arGlyphsInit();
        $text = $this->arGlyphsPreConvert($text);
        return $text;
    }

    private function arGlyphsDecodeEntities($text, $exclude = [])
    {
        $table = array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT
, 'UTF-8'));
        $table['&apos;'] = "'";
        $newtable = array_diff($table, $exclude);

        $text = preg_replace_callback('/&(#x?)?([A-Fa-f0-9]+);/u', function ($ma
tches) use ($newtable, $exclude) {
            return $this->arGlyphsDecodeEntities2($matches[1], $matches[2], $mat
ches[0], $newtable, $exclude);
        }, $text);

        return $text;
    }

    private function arGlyphsDecodeEntities2($prefix, $codepoint, $original, &$t
able, &$exclude)
    {
        if (!$prefix) {
            if (isset($table[$original])) {
                return $table[$original];
            } else {
                return $original;
            }
        }

        if ($prefix == '#x') {
            $codepoint = base_convert($codepoint, 16, 10);
        }

        $str = '';

        if ($codepoint < 0x80) {
            $str = chr((int)$codepoint);
        } elseif ($codepoint < 0x800) {
            $str = chr(0xC0 | ((int)$codepoint >> 6)) . chr(0x80 | ((int)$codepo
int & 0x3F));
        } elseif ($codepoint < 0x10000) {
            $str = chr(0xE0 | ((int)$codepoint >> 12)) . chr(0x80 | (((int)$code
point >> 6) & 0x3F)) .
                   chr(0x80 | ((int)$codepoint & 0x3F));
        } elseif ($codepoint < 0x200000) {
            $str = chr(0xF0 | ((int)$codepoint >> 18)) . chr(0x80 | (((int)$code
point >> 12) & 0x3F)) .
                   chr(0x80 | (((int)$codepoint >> 6) & 0x3F)) . chr(0x80 | ((in
t)$codepoint & 0x3F));
        }

        if (in_array($str, $exclude, true)) {
            return $original;
        } else {
            return $str;
        }
    }
}
