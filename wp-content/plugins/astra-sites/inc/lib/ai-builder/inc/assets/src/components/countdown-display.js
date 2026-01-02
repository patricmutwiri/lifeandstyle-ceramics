import React from 'react';
import { formatNumber } from '../hooks/use-countdown-timer';

// Reusable Timer Display Component
const CountdownDisplay = ( {
	timeLeft,
	variant = 'default', // 'default', 'compact'
	className = '',
} ) => {
	const isCompact = variant === 'compact';

	const containerClasses = isCompact
		? `flex items-center gap-3 ${ className }`
		: `flex justify-center gap-2 ${ className }`;

	const boxClasses = isCompact
		? 'flex flex-col items-center border border-white border-opacity-20 rounded-lg justify-center w-[48px] h-[48px]'
		: 'flex flex-col items-center bg-gray-800/50 rounded-lg px-3 py-1 min-w-[50px] border border-gray-700';

	const textClasses = isCompact
		? 'text-lg font-semibold'
		: 'text-lg font-semibold';

	const labelClasses = isCompact ? 'text-[8px] uppercase' : 'text-[10px]';

	const labels = isCompact
		? [ 'DD', 'HRS', 'MIN', 'SEC' ]
		: [ 'Days', 'Hours', 'Mins', 'Secs' ];

	return (
		<div className={ containerClasses }>
			<div className={ boxClasses }>
				<span className={ textClasses }>
					{ formatNumber( timeLeft.days ) }
				</span>
				<span className={ labelClasses }>{ labels[ 0 ] }</span>
			</div>
			<div className={ boxClasses }>
				<span className={ textClasses }>
					{ formatNumber( timeLeft.hours ) }
				</span>
				<span className={ labelClasses }>{ labels[ 1 ] }</span>
			</div>
			<div className={ boxClasses }>
				<span className={ textClasses }>
					{ formatNumber( timeLeft.minutes ) }
				</span>
				<span className={ labelClasses }>{ labels[ 2 ] }</span>
			</div>
			<div className={ boxClasses }>
				<span className={ textClasses }>
					{ formatNumber( timeLeft.seconds ) }
				</span>
				<span className={ labelClasses }>{ labels[ 3 ] }</span>
			</div>
		</div>
	);
};

export default CountdownDisplay;
