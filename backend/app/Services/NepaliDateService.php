<?php

namespace App\Services;

use Carbon\Carbon;

class NepaliDateService
{
    /**
     * BS (Bikram Sambat) to AD (Gregorian) conversion data
     * Format: [BS Year] => [Start Date AD (month-day), Days in each month]
     */
    private const BS_CALENDAR = [
        2080 => ['start' => '04-13', 'months' => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30]],
        2081 => ['start' => '04-13', 'months' => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30]],
        2082 => ['start' => '04-14', 'months' => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30]],
        2083 => ['start' => '04-14', 'months' => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31]],
    ];

    /**
     * Nepali month names
     */
    private const NEPALI_MONTHS = [
        1 => 'बैशाख', 'जेठ', 'असार', 'श्रावण', 'भदौ', 'असोज',
        'कार्तिक', 'मंसिर', 'पुष', 'माघ', 'फाल्गुन', 'चैत्र'
    ];

    /**
     * Nepali day names
     */
    private const NEPALI_DAYS = [
        'आइतबार', 'सोमबार', 'मंगलबार', 'बुधबार', 'बिहिबार', 'शुक्रबार', 'शनिबार'
    ];

    /**
     * Convert AD date to BS date
     */
    public function adToBs(string $adDate): string
    {
        $date = Carbon::parse($adDate);
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        // Simplified conversion - in production use a proper library like "nepali-calendar"
        foreach (self::BS_CALENDAR as $bsYear => $data) {
            $startDate = Carbon::parse("$year-{$data['start']}");
            if ($date->gte($startDate)) {
                $daysDiff = $date->diffInDays($startDate);
                
                $bsMonth = 1;
                $bsDay = 1;
                
                foreach ($data['months'] as $daysInMonth) {
                    if ($daysDiff < $daysInMonth) {
                        $bsDay += $daysDiff;
                        break;
                    }
                    $daysDiff -= $daysInMonth;
                    $bsMonth++;
                }
                
                return sprintf('%04d-%02d-%02d', $bsYear, $bsMonth, $bsDay);
            }
        }
        
        return '2081-01-01'; // Fallback
    }

    /**
     * Convert BS date to AD date
     */
    public function bsToAd(string $bsDate): string
    {
        list($bsYear, $bsMonth, $bsDay) = explode('-', $bsDate);
        $bsYear = (int)$bsYear;
        $bsMonth = (int)$bsMonth;
        $bsDay = (int)$bsDay;

        if (!isset(self::BS_CALENDAR[$bsYear])) {
            throw new \InvalidArgumentException("BS year $bsYear not supported");
        }

        $data = self::BS_CALENDAR[$bsYear];
        $startDate = Carbon::parse("{$bsYear}-{$data['start']}");
        
        $daysToAdd = 0;
        for ($i = 1; $i < $bsMonth; $i++) {
            $daysToAdd += $data['months'][$i - 1];
        }
        $daysToAdd += ($bsDay - 1);
        
        $adDate = $startDate->copy()->addDays($daysToAdd);
        return $adDate->format('Y-m-d');
    }

    /**
     * Get current date in BS
     */
    public function getCurrentBsDate(): string
    {
        return $this->adToBs(now()->format('Y-m-d'));
    }

    /**
     * Format BS date in Nepali
     */
    public function formatNepaliDate(string $bsDate, bool $includeDay = false): string
    {
        list($year, $month, $day) = explode('-', $bsDate);
        
        $yearNp = $this->convertToNepaliNumerals($year);
        $monthNp = self::NEPALI_MONTHS[(int)$month] ?? '';
        $dayNp = $this->convertToNepaliNumerals($day);
        
        $formatted = "{$yearNp} साल {$monthNp} {$dayNp} गते";
        
        if ($includeDay) {
            $adDate = $this->bsToAd($bsDate);
            $carbonDate = Carbon::parse($adDate);
            $dayOfWeek = $carbonDate->dayOfWeek;
            $dayNameNp = self::NEPALI_DAYS[$dayOfWeek] ?? '';
            $formatted = "{$dayNameNp}, {$formatted}";
        }
        
        return $formatted;
    }

    /**
     * Convert English numerals to Nepali numerals
     */
    public function convertToNepaliNumerals(string $number): string
    {
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $nepali = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        
        return str_replace($english, $nepali, $number);
    }

    /**
     * Calculate days between two BS dates
     */
    public function daysBetween(string $bsDate1, string $bsDate2): int
    {
        $adDate1 = Carbon::parse($this->bsToAd($bsDate1));
        $adDate2 = Carbon::parse($this->bsToAd($bsDate2));
        
        return abs($adDate1->diffInDays($adDate2));
    }

    /**
     * Add days to BS date
     */
    public function addDays(string $bsDate, int $days): string
    {
        $adDate = Carbon::parse($this->bsToAd($bsDate));
        $newAdDate = $adDate->addDays($days);
        
        return $this->adToBs($newAdDate->format('Y-m-d'));
    }

    /**
     * Subtract days from BS date
     */
    public function subDays(string $bsDate, int $days): string
    {
        return $this->addDays($bsDate, -$days);
    }

    /**
     * Validate BS date format (YYYY-MM-DD)
     */
    public function isValidBsDate(string $bsDate): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $bsDate)) {
            return false;
        }
        
        list($year, $month, $day) = explode('-', $bsDate);
        
        if ($month < 1 || $month > 12) {
            return false;
        }
        
        if (!isset(self::BS_CALENDAR[$year])) {
            return false;
        }
        
        $maxDays = self::BS_CALENDAR[$year]['months'][$month - 1] ?? 0;
        if ($day < 1 || $day > $maxDays) {
            return false;
        }
        
        return true;
    }

    /**
     * Get Nepali month name
     */
    public function getNepaliMonthName(int $month): string
    {
        return self::NEPALI_MONTHS[$month] ?? '';
    }

    /**
     * Get current fiscal year BS
     */
    public function getCurrentFiscalYearBs(): string
    {
        $currentBs = $this->getCurrentBsDate();
        list($year, $month, ) = explode('-', $currentBs);
        
        // Nepali fiscal year starts from Shrawan (4th month)
        if ($month >= 4) {
            return $year . '/' . ($year + 1);
        } else {
            return ($year - 1) . '/' . $year;
        }
    }
}