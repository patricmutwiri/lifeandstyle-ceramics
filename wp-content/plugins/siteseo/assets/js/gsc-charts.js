jQuery(document).ready(function($){
  
	// Function to check if we should show sample data or zeros
	function should_show_sample_data(){
		let chartElements = document.querySelectorAll('[data-sample]');
		if(chartElements.length > 0){
			return chartElements[0].getAttribute('data-sample') === '1';
		}

		return true;
	}

	let actual_data = window.siteseo_chart_data;
	let is_sample_data = should_show_sample_data();

	let main_chart_data = is_sample_data ? {
		dates : ['jan', 'feb', 'march', 'april', 'may', 'jun', 'july', 'Aug'],
		impressions: [950000, 1100000, 1250000, 1300000, 1200000, 1000000, 900000, 1100000],
		clicks: [6000, 9000, 10000, 9500, 8700, 7500, 6800, 11000]
		} : {
		// here show actual data
		dates: actual_data.chart_data.dates || 0,
		impressions: actual_data.chart_data.impressions || 0,
		clicks: actual_data.chart_data.clicks || 0
	};

	let device_data = is_sample_data ? {
		total_clicks : '1.8k',
		device : ['Mobile', 'Desktop', 'Tablet'],
		clicks : [65, 30, 5]
		} : {
		total_clicks : actual_data.total_devices_clicks || 0,
		device: actual_data.device_audience.map(item => item.device),
		clicks: actual_data.device_audience.map(item => item.clicks)
	};

	let country_data = is_sample_data ? {
		name : ['United States', 'India', 'United Kingdom', 'Canada', 'Germany'],
		clicks : [1200, 850, 620, 400, 250]
		} : {
		name : actual_data.country_audience.map(item => item.country),
		clicks : actual_data.country_audience.map(item => item.clicks)
	};

	// Helper function to extract numeric values from arrays
	function extract_numeric_values(dataArray){
		if(!dataArray || !Array.isArray(dataArray)) return [];
		
		// Filter out only numeric values and non-array objects
		return dataArray.filter(item => typeof item === 'number' && !isNaN(item));
	}

	let keyword_line_data = is_sample_data ? {
		top3 : [30, 31, 32, 32, 31, 28, 29, 30],
		pos4_10 : [38, 42, 45, 47, 44, 43, 35, 44],
		pos11_50 : [25, 20, 18, 16, 17, 22, 30, 22],
		pos50_100 : [6, 4, 4, 3, 4, 7, 9, 3],
		dates : ['Nov 1', 'Nov 10', 'Nov 20', 'Dec 1', 'Dec 10', 'Dec 20', 'Jan 1', 'Jan 10']
	} : {
		top3: extract_numeric_values(actual_data.keyword_data.top3) || 0,
		pos4_10: extract_numeric_values(actual_data.keyword_data.pos4_10) || 0,
		pos11_50: extract_numeric_values(actual_data.keyword_data.pos11_50) || 0,
		pos50_100: extract_numeric_values(actual_data.keyword_data.pos50_100) || 0,
		dates: actual_data.keyword_data.dates,
	};

	let keyword_bar_data = is_sample_data ? [5, 10, 52, 30] : [
		actual_data.keyword_distribution.top3 || 0,
		actual_data.keyword_distribution.pos4_10 || 0,
		actual_data.keyword_distribution.pos11_50 || 0,
		actual_data.keyword_distribution.pos50_100 || 0
	];

	let metric_chart_data = is_sample_data ? {
		impressions: [100, 90, 80, 70, 60, 50, 40, 30],
		clicks: [10, 11, 10, 12, 11, 12, 13, 14],
		ctr: [10.0, 12.2, 12.5, 17.1, 18.3, 24.0, 32.5, 46.6],
		position: [15, 16, 17, 18, 19, 20, 21, 22],
		dates: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug']
	} : {
		impressions: actual_data.chart_data.impressions || 0,
		clicks: actual_data.chart_data.clicks || 0,
		ctr: actual_data.chart_data.ctr || 0,
		position: actual_data.chart_data.position || 0,
		dates:actual_data.chart_data.dates || 0
	};

	// Main Chart
	if(document.getElementById('seo_statistics')){
		let ctx = document.getElementById('seo_statistics').getContext('2d');
		let seo_statistics = new Chart(ctx, {
			type: 'line',
			data: {
				labels: main_chart_data.dates,
				datasets: [
					{
						label: 'Search Impressions',
						data: main_chart_data.impressions,
						borderColor: is_sample_data ? 'rgba(0, 123, 255, 1)' : 'rgba(0, 123, 255, 0.8)',
						backgroundColor: is_sample_data ? 'rgba(0, 123, 255, 0.2)' : 'rgba(0, 123, 255, 0.1)',
						fill: true,
						yAxisID: 'yImpressions',
						tension: 0.4,
						pointRadius: 0.1,
						borderWidth:2
					},
					{
						label: 'Search Clicks',
						data: main_chart_data.clicks,
						borderColor: is_sample_data ? 'rgba(40, 167, 69, 1)' : 'rgba(40, 167, 69, 0.8)',
						backgroundColor: is_sample_data ? 'rgba(40, 167, 69, 0.1)' : 'rgba(40, 167, 69, 0.1)',
						fill: true,
						yAxisID: 'yClicks',
						tension: 0.4,
						pointRadius: 0.1,
						borderWidth:2
					}
				]
			},
			options: {
				responsive: true,
				interaction: {
					mode: 'index',
					intersect: false
				},
				plugins: {
					legend: {
						position: 'bottom'
					},
					title: {
						display: false
					},
					tooltip: {
						enabled: true
					}
				},
				scales: {
					x: {
						grid: {
							display: false
						},
						ticks: {
							stepSize: 10,
							callback: function(value,index){
								return index % 10 === 0 ? this.getLabelForValue(value) : '';
							},
						},
					},
					yImpressions: {
						type: 'linear',
						position: 'left',
						beginAtZero: true,
						ticks: {
							stepSize: 3,
							callback: function(value) {
								return value >= 1000 ? (value / 1000) + 'K' : value;
							},
						},
						grid: {
							display: true
						},
						border: {
							display: true
						}
					},
					yClicks: {
						type: 'linear',
						position: 'right',
						beginAtZero: true, 
						ticks: {
							stepSize: 0.5,
							callback: function(value) {
								return value >= 1000 ? (value / 1000) + 'K' : value;
							}
						},
						grid: {
							display: false
						},
						border: {
							display: false
						}
					}
				}
			}
		});
	}

	// Keyword Multi Line Chart
	if(document.getElementById('siteseo_keyword_muti_line_chart')){
		let keywords = document.getElementById('siteseo_keyword_muti_line_chart').getContext('2d');
		
		// Check if we have actual data for the line chart
		let has_actual_line_data = !is_sample_data && keyword_line_data.top3.length > 0 && keyword_line_data.dates.length > 0;
			
		let all_data = [
			...keyword_line_data.top3,
			...keyword_line_data.pos4_10,
			...keyword_line_data.pos11_50,
			...keyword_line_data.pos50_100
		];
	
		let max_value = Math.max(...all_data);

		// Improved dynamic Y-axis max calculation
		let y_axis_max;
		if(max_value <= 5){
			// For very small values, use smaller increments
			y_axis_max = Math.ceil(max_value / 1) * 1; // round up to nearest whole number
			if(y_axis_max === max_value) y_axis_max += 1; // add some padding
		} else if(max_value <= 20){
			// For medium values, use smaller increments
			y_axis_max = Math.ceil(max_value / 5) * 5;
		} else {
			// For larger values, use normal increments
			y_axis_max = Math.ceil(max_value / 10) * 10;
		}

		// Ensure minimum y_axis_max for very small values
		if (y_axis_max < 5) y_axis_max = 5;
		
		let siteseo_keyword_muti_line_chart = new Chart(keywords, {
			type: 'line',
			data: {
				labels: has_actual_line_data ? keyword_line_data.dates : keyword_line_data.dates,
				datasets: [
					{
						label: 'Top 3 Position',
						data: has_actual_line_data ? keyword_line_data.top3 : keyword_line_data.top3,
						borderColor: 'rgba(0, 90, 224, 0.7)',
						backgroundColor: 'rgba(0, 90, 224, 0.15)',
						fill: 'origin',
						tension: 0.4,
						borderWidth: 2,
						pointRadius: 0.1,
						pointHoverRadius: 5,
						pointBackgroundColor: 'rgba(0, 90, 224, 1)',
						pointBorderColor: '#ffffff',
						pointBorderWidth: 1,
						pointStyle: 'circle'
					},
					{
						label: '4-10 Position',
						data: has_actual_line_data ? keyword_line_data.pos4_10 : keyword_line_data.pos4_10,
						borderColor: 'rgba(0, 170, 9, 0.7)',
						backgroundColor: 'rgba(0, 170, 9, 0.15)',
						fill: 'origin',
						tension: 0.4,
						borderWidth: 2,
						pointRadius: 0.1,
						pointHoverRadius: 5,
						pointBackgroundColor: 'rgba(0, 170, 9, 1)',
						pointBorderColor: '#ffffff',
						pointBorderWidth: 1,
						pointStyle: 'circle'
					},
					{
						label: '11-50 Position',
						data: has_actual_line_data ? keyword_line_data.pos11_50 : keyword_line_data.pos11_50,
						borderColor: 'rgba(255, 140, 0, 0.7)',
						backgroundColor: 'rgba(255, 140, 0, 0.15)',
						fill: 'origin',
						tension: 0.4,
						borderWidth: 2,
						pointRadius: 0.1,
						pointHoverRadius: 5,
						pointBackgroundColor: 'rgba(255, 140, 0, 1)',
						pointBorderColor: '#ffffff',
						pointBorderWidth: 1,
						pointStyle: 'circle'
					},
					{
						label: '50-100 Position',
						data: has_actual_line_data ? keyword_line_data.pos50_100 : keyword_line_data.pos50_100,
						borderColor: 'rgba(220, 20, 60, 0.7)',
						backgroundColor: 'rgba(220, 20, 60, 0.15)',
						fill: 'origin',
						tension: 0.4,
						borderWidth: 2,
						pointRadius: 0.1,
						pointHoverRadius: 5,
						pointBackgroundColor: 'rgba(220, 20, 60, 1)',
						pointBorderColor: '#ffffff',
						pointBorderWidth: 1,
						pointStyle: 'circle'
					}
				]
			},
			options: {
				responsive: true,
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							usePointStyle: true,
							padding: 20,
							boxWidth: 12
						}
					},
					title: {
						display: true,
						text: has_actual_line_data ? 'Keyword Position Trends' : 'Top Positions'
					},
					tooltip: {
						enabled: true,
						mode: 'index',
						intersect: false,
						backgroundColor: 'rgba(0, 0, 0, 0.8)',
						padding: 12,
						cornerRadius: 6,
						displayColors: true,
						callbacks: {
							title: function(tooltipItems) {
								return tooltipItems[0].label;
							},
							label: function(context) {
								return `${context.dataset.label}: ${context.parsed.y}`;
							}
						}
					}
				},
				interaction: {
					mode: 'index',
					intersect: false
				},
				elements: {
					line: {
						cubicInterpolationMode: 'monotone'
					}
				},
				scales: {
					x: {
						grid: {
							display: false
						},
						border: {
							display: false
						},
						ticks: {
							font: {
								size: 11
							},
							callback: function(value, index, ticks) {
								// Show label for every 10th date only
								return index % 8 === 0 ? this.getLabelForValue(value) : '';
							}
						}
					},
					y: {
						beginAtZero: true,
						max: y_axis_max,
						grid: {
							color: 'rgba(0, 0, 0, 0.05)'
						},
						ticks: {
							stepSize: y_axis_max <= 10 ? 1 : (y_axis_max <= 20 ? 5 : 10),
							callback: function(value) {
								return value;
							},
							font: {
								size: 11
							}
						}
					}
				},
				animation: {
					duration: 1000,
					easing: 'easeOutQuart'
				},
				hover: {
					mode: 'index',
					intersect: false
				}
			}
		});
	}
	
	// Keyword Bar Chart
	if(document.getElementById('siteseo_keyword_bar_chart')){
		let bar_chart = document.getElementById('siteseo_keyword_bar_chart').getContext('2d');
		let max_bar_value = Math.max(...keyword_bar_data);
		let y_axis_max_bar = Math.ceil(max_bar_value / 10) * 10; // round up to nearest 10
		if(y_axis_max_bar < 50) y_axis_max_bar = 50; // minimum 50
		if(y_axis_max_bar > 100) y_axis_max_bar = 100; // maximum 100


		let siteseo_keyword_bar_chart = new Chart(bar_chart, {
			type: 'bar',
			data: {
				labels: ['Top 3 Position', '4-10 Position', '11-50 Position', '50-100 Position'],
				datasets: [{
					data: keyword_bar_data,
					backgroundColor: [
						'#1d6ecc',
						'#3aaf60',
						'#f59b2d',
						'#e3485d'
					],
					borderWidth: 0
				}]
			},
			options: {
				responsive: true,
				plugins: {
				  legend: { display: false },
					title: {
					  display: true,
					  text: is_sample_data ? 'Keyword Distribution' : 'Keyword Position Distribution'
					},
					tooltip: {
					  callbacks: {
						label: function(context) {
						  return `${context.parsed.y}%`;
						}
					  }
					}
				},
				scales: {
				   x: {
				  	grid: {
				  		display: false
				  	},
				  	border: {
				  		display: false
				  	}
				  },
				  
				  y: {
					beginAtZero: true,
					min: 0,
					max: y_axis_max_bar,
					ticks: {
						stepSize: Math.round(y_axis_max_bar / 2),
						callback: function(value){
							return value + "%";
						  }
						}
					}
				}
			}
		});
	}
	
	// Metric Charts (Impressions, Clicks, CTR, Position)
	let chart_config = {
		type: 'line',
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: { 
						enabled: true,
						mode: 'index',
						intersect: false,
						callbacks: {
							title: function(tooltipItems){
								// Show date instead of "Day X"
								let data_index = tooltipItems[0].dataIndex;

								if(metric_chart_data.dates && metric_chart_data.dates[data_index]){
									return metric_chart_data.dates[data_index];
								}
								return `Day ${data_index + 1}`;
							},
							label: function(context){
								let label = context.dataset.label || '';
								if (label) {
									label += ': ';
								}
								if(context.parsed.y !== null){
									// Format numbers appropriately
									if(context.dataset.label?.toLowerCase().includes('ctr')){
										label += context.parsed.y.toFixed(2) + '%';
									} else if(context.dataset.label?.toLowerCase().includes('position')){
										label += context.parsed.y.toFixed(1);
									} else{
										label += context.parsed.y.toLocaleString();
									}
								}
								return label;
							}
						}
					}
				},
				scales: {
					x: { display: false },
					y: { 
						display: false,
						beginAtZero: true
					}
				},
				elements: {
					point: { 
						radius: 0.01,
						hoverRadius: 6,
						hoverBackgroundColor: '#fff',
						hoverBorderWidth: 2
					}
				},
				layout: {
					padding: { top: 5, bottom: 5, left: 0, right: 0 }
				},
				tension: 0.4,
				interaction: {
					mode: 'index',
					intersect: false
				},
				hover: {
					mode: 'index',
					intersect: false
				}
			}
		};

	// Function to generate colors based on performance using only 4 colors
	function get_performance_based_color(metric_type, data){
		if(!data || data.length < 2) return '#3498db'; // Default blue
		
		let first_value = data[0];
		let last_value = data[data.length - 1];
		let percentageChange = ((last_value - first_value) / first_value) * 100;
		
		// Define the 4 colors
		const colors = {
			blue: '#3498db',
			green: '#2ecc71', 
			red: '#e74c3c',
			yellow: '#f39c12'
		};
		
		// Performance logic for each metric type
		switch(metric_type){
			case 'impressions':
				// Higher is better
				if(last_value > first_value * 1.1) return colors.green;
				if(last_value > first_value) return colors.blue;
				if(last_value >= first_value * 0.9) return colors.yellow;
				return colors.red;
				
			case 'clicks':
				// Higher is better  
				if(last_value > first_value * 1.15) return colors.green;   // >15% increase - Green
				if(last_value > first_value) return colors.blue;		   // Any increase - Blue
				if(last_value >= first_value * 0.85) return colors.yellow; // <15% decrease - Yellow
				return colors.red;										// >15% decrease - Red
				
			case 'ctr':
				// Higher is better
				if(last_value > first_value * 1.2) return colors.green;	// >20% increase - Green
				if(last_value > first_value) return colors.blue;		   // Any increase - Blue  
				if(last_value >= first_value * 0.8) return colors.yellow;  // <20% decrease - Yellow
				return colors.red;										// >20% decrease - Red
				
			case 'position':
				// Lower is better
				if(last_value < first_value * 0.8) return colors.green;	// >20% improvement - Green
				if(last_value < first_value) return colors.blue;		   // Any improvement - Blue
				if(last_value <= first_value * 1.2) return colors.yellow;  // <20% decline - Yellow
				return colors.red;										// >20% decline - Red
				
			default:
				return colors.blue; // Default blue
		}
	}

	// Function to create metric charts
	let create_chart = (ctxId, data, metricType) => {
		if(!document.getElementById(ctxId)) return;

		let ctx = document.getElementById(ctxId).getContext('2d');

		// Get color based on performance
		let chart_color = get_performance_based_color(metricType, data);

		let gradient = ctx.createLinearGradient(0, 0, 0, 80);
		gradient.addColorStop(0, `rgba(${hex_to_rgb(chart_color)}, 0.2)`);
		gradient.addColorStop(1, `rgba(${hex_to_rgb(chart_color)}, 0)`);
		
		// Get label for dataset
		const labels = {
			'impressions': 'Impressions',
			'clicks': 'Clicks',
			'ctr': 'CTR',
			'position': 'Position'
		};

		new Chart(ctx, {
			...chart_config,
			data: {
				// Use dates if available, otherwise use generic day labels
				labels: metric_chart_data.dates || Array.from({ length: data.length }, (_, i) => `Day ${i + 1}`),
				datasets: [{
					label: labels[metricType] || metricType,
					data: data,
					borderColor: chart_color,
					borderWidth: 2,
					backgroundColor: gradient,
					fill: true,
					pointBackgroundColor: chart_color,
					pointBorderColor: '#fff',
					pointBorderWidth: 2,
					pointHoverBackgroundColor: '#fff',
					pointHoverBorderColor: chart_color,
					pointHoverBorderWidth: 3
				}]
			}
		});
	};

	// Helper function to convert hex to rgb
	function hex_to_rgb(hex){
		hex = hex.replace(/^#/, '');
		let bigint = parseInt(hex, 16);
		let r = (bigint >> 16) & 255;
		let g = (bigint >> 8) & 255;
		let b = bigint & 255;
		return `${r}, ${g}, ${b}`;
	}

	// Create metric charts
	if(document.getElementById('siteseo_impressions_chart')){
		create_chart('siteseo_impressions_chart', metric_chart_data.impressions, 'impressions');
	}

	if(document.getElementById('siteseo_clicks_chart')){
		create_chart('siteseo_clicks_chart', metric_chart_data.clicks, 'clicks');
	}

	if(document.getElementById('siteseo_ctr_chart')){
		create_chart('siteseo_ctr_chart', metric_chart_data.ctr, 'ctr');
	}

	if(document.getElementById('siteseo_position_chart')){
		create_chart('siteseo_position_chart', metric_chart_data.position, 'position');
	}

	// Country data
	let ctx = document.getElementById('siteseo_country_statics').getContext('2d');
	let my_Chart = new Chart(ctx, {
		type: 'bar',
		data: {
			labels: country_data.name,
			datasets: [{
				label: 'Clicks',
				data: country_data.clicks,
				backgroundColor: '#1d6ecc',
				borderRadius: 5,
				barThickness: 'flex',
				maxBarThickness: 20,
			}]
		},
		options: {
			indexAxis: 'y',
			responsive: false, // Disable responsive
			maintainAspectRatio: false, // Disable aspect ratio maintenance
			scales: {
				x: {
					beginAtZero: true,
					grid: { display: false }
				},
				y: {
					grid: { display: false }
				}
			},
			plugins: {
				legend: { display: false },
				tooltip: { enabled: true }
			}
		}
	});
	
	// Device data
	let devices_audience = document.getElementById('siteseo_device_statics').getContext('2d');
	let pie_chart = new Chart(devices_audience, {
		type: 'doughnut',
		data: {
			labels: device_data.device,
			datasets: [{
				data: device_data.clicks,
				backgroundColor : ['#1d6ecc','#3aaf60', '#f59b2d'],
				borderWidth: 0
			}]
		},
		options: {
			responsive: false, // Keep this as false
			maintainAspectRatio: false, // Keep this as false
			cutout: '70%',
			plugins: {
				legend: {
					display: true,
					position: 'bottom',
					labels: {
						usePointStyle: true,
						pointStyle: 'circle'
					}
				},
				tooltip: {
					enabled: true,
					callbacks: {
						label: function(context) {
							return `${context.label}: ${context.raw} clicks`;
						}
					}
				},
				title: {
					display: false
				}
			}
		},
		plugins: [{
			id: 'centerText',
			afterDraw: (chart) => {
				const {ctx, chartArea: {width, height}} = chart;
				ctx.save();
				
				// Display total clicks
				ctx.font = 'bold 22px sans-serif';
				ctx.fillStyle = '#111';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'middle';
				ctx.fillText(device_data.total_clicks, width / 2, height / 2 - 10);
				
				// Display "clicks" label
				ctx.font = '12px sans-serif';
				ctx.fillStyle = '#888';
				ctx.fillText('Total clicks', width / 2, height / 2 + 12);
				
				ctx.restore();
			}
		}]
	});

});