import { useState, useEffect } from 'react';

// Sale timing configuration - Using UTC
export const getSaleDates = () => {
	// Sale starts: November 17, 2025 at 6:30 AM UTC (12:00 PM IST)
	const saleStart = new Date( Date.UTC( 2025, 10, 17, 6, 30, 0 ) ); // Month is 0-indexed (10 = November)

	// Black Friday sale ends: November 29, 2025 at 9:30 AM IST (4:00 AM UTC)
	const blackFridaySaleEndDate = new Date(
		Date.UTC( 2025, 10, 29, 4, 0, 0 )
	);

	// Small Business Saturday: November 30, 2025 at 9:30 AM IST (4:00 AM UTC)
	const smallBusinessSatEndDate = new Date(
		Date.UTC( 2025, 10, 30, 4, 0, 0 )
	);

	// Give Back Sunday: December 1, 2025 at 9:30 AM IST (4:00 AM UTC)
	const giveBackSundayEndDate = new Date( Date.UTC( 2025, 11, 1, 4, 0, 0 ) );

	// Cyber Monday: December 6, 2025 at 9:30 AM IST (4:00 AM UTC)
	const cyberMondayEndDate = new Date( Date.UTC( 2025, 11, 6, 4, 0, 0 ) );

	return {
		saleStart,
		blackFridaySaleEndDate,
		smallBusinessSatEndDate,
		giveBackSundayEndDate,
		cyberMondayEndDate,
	};
};

// Custom hook for countdown timer
export const useCountdownTimer = () => {
	const [ timeLeft, setTimeLeft ] = useState( {
		days: 0,
		hours: 0,
		minutes: 0,
		seconds: 0,
	} );
	const [ isActive, setIsActive ] = useState( false );
	const [ showTimer, setShowTimer ] = useState( true );
	const [ currentSalePeriod, setCurrentSalePeriod ] =
		useState( 'black-friday' ); // default

	useEffect( () => {
		const calculateTimeLeft = () => {
			const now = new Date();

			const {
				saleStart,
				blackFridaySaleEndDate,
				smallBusinessSatEndDate,
				giveBackSundayEndDate,
				cyberMondayEndDate,
			} = getSaleDates();

			let targetDate;
			let active = false;
			let salePeriod = 'black-friday'; // default

			// Check each timer in sequence
			if ( now >= saleStart && now < blackFridaySaleEndDate ) {
				// Black Friday sale - countdown to end
				targetDate = blackFridaySaleEndDate;
				active = true;
				salePeriod = 'black-friday';
			} else if (
				now >= blackFridaySaleEndDate &&
				now < smallBusinessSatEndDate
			) {
				// Small Business Saturday - countdown to end
				targetDate = smallBusinessSatEndDate;
				active = true;
				salePeriod = 'small-business-saturday';
			} else if (
				now >= smallBusinessSatEndDate &&
				now < giveBackSundayEndDate
			) {
				// Give Back Sunday - countdown to end
				targetDate = giveBackSundayEndDate;
				active = true;
				salePeriod = 'give-back-sunday';
			} else if (
				now >= giveBackSundayEndDate &&
				now < cyberMondayEndDate
			) {
				// Cyber Monday - countdown to end
				targetDate = cyberMondayEndDate;
				active = true;
				salePeriod = 'cyber-monday';
			} else {
				active = false;
				setShowTimer( false );
				return { days: 0, hours: 0, minutes: 0, seconds: 0 };
				// All sales have ended
			}

			setIsActive( active );
			setCurrentSalePeriod( salePeriod );

			const difference = targetDate.getTime() - now.getTime();

			if ( difference > 0 ) {
				const days = Math.floor( difference / ( 1000 * 60 * 60 * 24 ) );
				const hours = Math.floor(
					( difference / ( 1000 * 60 * 60 ) ) % 24
				);
				const minutes = Math.floor( ( difference / 1000 / 60 ) % 60 );
				const seconds = Math.floor( ( difference / 1000 ) % 60 );

				return {
					days,
					hours,
					minutes,
					seconds,
				};
			}

			// When a timer reaches zero, move to the next one automatically
			return { days: 0, hours: 0, minutes: 0, seconds: 0 };
		};

		// Update immediately
		const initialTime = calculateTimeLeft();
		setTimeLeft( initialTime );

		// Update every second
		const timer = setInterval( () => {
			const newTime = calculateTimeLeft();
			setTimeLeft( newTime );
		}, 1000 );

		return () => clearInterval( timer );
	}, [] );

	return {
		timeLeft,
		isActive,
		showTimer,
		currentSalePeriod,
	};
};

// Utility function to format number with leading zero
export const formatNumber = ( num ) => {
	return num.toString().padStart( 2, '0' );
};
