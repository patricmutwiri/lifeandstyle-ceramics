<?php

namespace CookieAdmin;

if(!defined('COOKIEADMIN_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class CookieCategorizer {

    /**
     * Takes the raw output from the scanner and enriches it with data from the knowledge base.
     *
     * @param array $found_cookies The array of cookies from the Scanner class.
     * @return array The enriched list of cookies.
     */
    static function categorize_cookies(array $found_cookies, array $categorized_cookies) {
        if (empty($found_cookies)) {
            return [];
        }

        $prefixes = self::generate_query_prefixes(array_keys($found_cookies));
	
        if (empty($prefixes)) {
            return $found_cookies;
        }

        $db_candidates = self::fetch_candidates_from_db($prefixes);

        if (empty($db_candidates)) {
            return $found_cookies; 
        }
	
        return self::match_and_enrich_cookies($found_cookies, $db_candidates, $categorized_cookies);
    }


    static function generate_query_prefixes(array $cookie_names): array {
        $prefixes = [];
        foreach ($cookie_names as $name) {
			
            $parts = explode('_', $name, 2);
            $prefix = $parts[0];

            // Add a simple rule to avoid tiny, useless prefixes like "a" or "b"
            if (strlen($prefix) >= 3) {
                $prefixes[] = $prefix;
            } else {
                // If prefix is too short, use the full name up to a limit
                $prefixes[] = substr($name, 0, 3);
            }
        }
        return array_unique($prefixes);
    }


    static function fetch_candidates_from_db(array $prefixes): array {
	
		if (empty($prefixes)) {
			return [];
		}
			
		$cookies_info = cookieadmin_load_cookies_csv($prefixes, 1);
			
		if(empty($cookies_info) || is_wp_error($cookies_info)){
			return [];
		}

		return $cookies_info;
    }


    static function match_and_enrich_cookies(array $found_cookies, array $db_candidates, array $old_categorized_cookies): array {
        $categorized_cookies = [];
        $old_categorized_cookies = [];
        $remove_cookies = [];

        foreach ($found_cookies as $scanned_name => $cookie_data) {
            $best_match = null;
            $longest_match_length = 0;
			
            foreach ($db_candidates as $candidate) {
				
                $candidate_name = $candidate['cookie_name'];
                
                // Rule 1: Wildcard Match
                if ($candidate['wildcard'] == 1 && strpos($scanned_name, $candidate_name) === 0) {
                    // "Best" is defined as the longest matching prefix.
                    if (strlen($candidate_name) > $longest_match_length) {
                        $longest_match_length = strlen($candidate_name);
                        $best_match = $candidate;
                    }
                }
                // Rule 2: Exact Match
                else if ($candidate['wildcard'] == 0 && $scanned_name === $candidate_name) {
                    if (strlen($candidate_name) > $longest_match_length) {
                        $longest_match_length = strlen($candidate_name);
                        $best_match = $candidate;
                        break;
                    }
                }
            }
			
            if (!empty($best_match)) {
			
				if(!empty($old_categorized_cookies) && array_key_exists($best_match['cookie_name'], $old_categorized_cookies)){
					$remove_cookies[] = $scanned_name;
					continue;
				}

				$cookie_name = $best_match['cookie_name'];
                $categorized_cookies[$cookie_name]['cookie_name'] = $cookie_name;
                $categorized_cookies[$cookie_name]['raw_name'] = $scanned_name;
				$categorized_cookies[$cookie_name]['category'] = $best_match['category'];
				$categorized_cookies[$cookie_name]['description'] = $best_match['description'];
				$categorized_cookies[$cookie_name]['patterns'] = $best_match['patterns'];
				
            }
        }
		$categorized_cookies['remove_cookies'] = $remove_cookies;
        return $categorized_cookies;
    }
}