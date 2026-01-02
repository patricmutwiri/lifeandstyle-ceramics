/**
 * RenderQueue
 *
 * A minimal FIFO queue that ensures tasks (typically badge-rendering functions)
 * run sequentially. Tasks are functions that return a Promise (or an async function).
 *
 * Behavior:
 * - Tasks enqueued via enqueue(task) are executed in the order they were added.
 * - Only one task is processed at a time (sequential processing).
 * - If a task rejects, the error is caught and processing continues with the next task.
 *
 * Usage example:
 *   const q = new RenderQueue();
 *   q.enqueue(async () => { await renderBadge(element); });
 *
 * This class is intentionally lightweight and not suitable for advanced features
 * such as concurrency control, task prioritization, or cancellation.
 */
class RenderQueue {
	/**
	 * A FIFO queue of tasks to be processed.
	 *
	 * @private
	 * @type {Array<Function>}
	 */
	#tasks;

	/**
	 * Indicates if the queue is currently processing tasks.
	 *
	 * @private
	 * @type {boolean}
	 */
	#processing;

	constructor() {
		this.#tasks = [];
		this.#processing = false;
	}

	/**
	 * Enqueue a task for sequential execution.
	 *
	 * @param {Function} task - A function that returns a Promise (or an async function).
	 * @return {void}
	 */
	enqueue( task ) {
		this.#tasks.push( task );
		if ( ! this.#processing ) {
			this.#process();
		}
	}

	/**
	 * Process queued tasks sequentially until the queue is empty.
	 *
	 * The method sets `this.processing` while working and clears it when done.
	 * Any error thrown by a task is caught and ignored so subsequent tasks still run.
	 *
	 * @private
	 * @return {Promise<void>}
	 */
	async #process() {
		this.#processing = true;
		while ( this.#tasks.length > 0 ) {
			const task = this.#tasks.shift();
			try {
				await task();
			} catch ( error ) {
				// Badge rendering task failed - continue with next task
			}
		}
		this.#processing = false;
	}
}

export default RenderQueue;
