<?php

namespace com;
/**
* IP geolocation query class Modified from CoolCode.CN
 * Due to the use of UTF8 encoding, if the pure IP address library is used, the encoding conversion of the returned result needs to be performed
 */
class IpLocation {
    /**
     * QQWry.Dat file pointer
     *
     * @var resource
     */
    private $fp;

    /**
     * Offset address of the first IP record
     *
     * @var int
     */
    private $firstip;

    /**
     * Offset address of the last IP record
     *
     * @var int
     */
    private $lastip;

    /**
     * The total number of IP records (excluding version information records)
     *
     * @var int
     */
    private $totalip;

    /**
     * Constructor that opens the QQWry.Dat file and initializes the information in the class
     *
     * @param string $filename
     * @return IpLocation
     */
    public function __construct($filename = "UTFWry.dat") {
        $this->fp = 0;
        if (($this->fp      = fopen(dirname(__FILE__).'/'.$filename, 'rb')) !== false) {
            $this->firstip  = $this->getlong();
            $this->lastip   = $this->getlong();
            $this->totalip  = ($this->lastip - $this->firstip) / 7;
        }
    }

    /**
     * Returns the read long integer
     *
     * @access private
     * @return int
     */
    private function getlong() {
        //Convert the read little-endian encoded 4 bytes into a long integer
        $result = unpack('Vlong', fread($this->fp, 4));
        return $result['long'];
    }

    /**
     * Returns the read 3-byte long integer
     *
     * @access private
     * @return int
     */
    private function getlong3() {
        //Convert the read little-endian encoded 3 bytes into a long integer
        $result = unpack('Vlong', fread($this->fp, 3).chr(0));
        return $result['long'];
    }

    /**
     * Return compressed IP addresses that can be compared
     *
     * @access private
     * @param string $ip
     * @return string
     */
    private function packip($ip) {
        // Convert the IP address to a long integer, if the IP address is wrong in PHP5, return False,
        // At this time, intval converts Flase to integer -1, and then compresses it into a big-endian encoded string
        return pack('N', intval(ip2long($ip)));
    }

    /**
     * return the read string
     *
     * @access private
     * @param string $data
     * @return string
     */
    private function getstring($data = "") {
        $char = fread($this->fp, 1);
        while (ord($char) > 0) { // The string is saved in C format, terminated with \0
            $data .= $char; // Concatenate the read characters after the given string
            $char   = fread($this->fp, 1);
        }
        return $data;
    }

    /**
     * Return to area information
     *
     * @access private
     * @return string
     */
    private function getarea() {
        $byte = fread($this->fp, 1); // flag byte
        switch (ord($byte)) {
            case 0: // no locale information
                $area = "";
                break;
            case 1:
            case 2: // The flag byte is 1 or 2, indicating that the area information is redirected
                fseek($this->fp, $this->getlong3());
                $area = $this->getstring();
                break;
            default: // otherwise, indicates that the area information is not redirected
                $area = $this->getstring($byte);
                break;
        }
        return $area;
    }

    /**
     * Return the location information based on the given IP address or domain name
     *
     * @access public
     * @param string $ip
     * @return array
     */
    public function getlocation($ip='') {
        if (!$this->fp) return null; // If the data file is not opened correctly, return null
if(empty($ip)) $ip = get_client_ip();
        $location['ip'] = gethostbyname($ip); // Convert the input domain name to IP address
        $ip = $this->packip($location['ip']); // Convert the input IP address to a comparable IP address
                                                // Invalid IP addresses will be converted to 255.255.255.255
        // binary search
        $l = 0; // lower bound of search
        $u = $this->totalip; // upper bound of search
        $findip = $this->lastip; // If not found, return the last IP record (version information of QQWry.Dat)
        while ($l <= $u) { // When the upper bound is less than the lower bound, the search fails
            $i = floor(($l + $u) / 2); // compute approximate middle records
            fseek($this->fp, $this->firstip + $i * 7);
            $beginip = strrev(fread($this->fp, 4)); // Get the start IP address of the intermediate record
            // The function of strrev here is to convert the compressed IP address of little-endian into big-endian format
            // for comparison, the same later.
            if ($ip < $beginip) { // When the user's IP is less than the start IP address of the intermediate record
                $u = $i - 1; // Change the upper bound of the search to the middle record minus one
            }
            else {
                fseek($this->fp, $this->getlong3());
                $endip = strrev(fread($this->fp, 4)); // Get the end IP address of the intermediate record
                if ($ip > $endip) { // When the user's IP is greater than the end IP address of the intermediate record
                    $l = $i + 1; // Change the lower bound of the search to the middle record plus one
                }
                else { // When the user's IP is within the IP range recorded in the middle
                    $findip = $this->firstip + $i * 7;
                    break; // means to find the result and exit the loop
                }
            }
        }

        //Get the IP geolocation information found
        fseek($this->fp, $findip);
        $location['beginip'] = long2ip($this->getlong()); // the start address of the user IP range
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        $location['endip'] = long2ip($this->getlong()); // The end address of the user IP range
        $byte = fread($this->fp, 1); // flag byte
        switch (ord($byte)) {
            case 1: // The flag byte is 1, indicating that both country and region information are redirected at the same time
                $countryOffset = $this->getlong3(); // redirect address
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1); // flag byte
                switch (ord($byte)) {
                    case 2: // The flag byte is 2, indicating that the country information is redirected again
                        fseek($this->fp, $this->getlong3());
                        $location['country'] = $this->getstring();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area'] = $this->getarea();
                        break;
                    default: // Otherwise, the country information is not redirected
                        $location['country']    = $this->getstring($byte);
                        $location['area']       = $this->getarea();
                        break;
                }
                break;
            case 2: // The flag byte is 2, indicating that the country information is redirected
                fseek($this->fp, $this->getlong3());
                $location['country'] = $this->getstring();
                fseek($this->fp, $offset + 8);
                $location['area'] = $this->getarea();
                break;
            default: // Otherwise, the country information is not redirected
                $location['country'] = $this->getstring($byte);
                $location['area'] = $this->getarea();
                break;
        }
        if (trim($location['country']) == 'CZ88.NET') { // CZ88.NET indicates that there is no valid information
            $location['country'] = 'unknown';
        }
        if (trim($location['area']) == 'CZ88.NET') {
            $location['area'] = '';
        }
        return $location;
    }

    /**
     * Destructor to automatically close open files after page execution ends.
     *
     */
    public function __destruct() {
        if ($this->fp) {
            fclose($this->fp);
        }
        $this->fp = 0;
    }

}