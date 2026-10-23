# Diagnosing final archive performance

Every queue attempt emits one `Final archive attempt` entry to the existing daily
`performance` channel (`storage/logs/performance-YYYY-MM-DD.log`). It records only
the numeric archive ID, attempt number, outcome, durations, counts and byte sizes.
Project names, filenames, paths, participant details, document contents and
exception messages are not included.

`queue_wait_ms` estimates time from request creation to the first worker attempt,
using the database timestamp's precision. It is `null` on retries because time
since request creation then includes previous processing and backoff.
`request_age_ms` remains available on every attempt.

`duration_ms` is elapsed time for the current attempt, including temporary-file
cleanup. Phase times use the monotonic clock and are summed when a phase repeats:

| Phase | Work measured |
| --- | --- |
| `archive_start` | Marking the attempt as processing |
| `snapshot` | Database snapshot and relation loading |
| `serialize` | Structured payload, index HTML and JSON generation |
| `pdf` | All application, financial, attendance, expense and feedback PDF generation |
| `file_copy` | Source storage access, temporary copying, checksum and ZIP registration |
| `generated_write` | Writing generated reports and index HTML to temporary files |
| `activity_csv` | Reading and writing the activity history CSV |
| `zip_finalize` | ZIP close: deferred compression and final ZIP writing |
| `archive_store` | Copying the finished ZIP to configured archive storage |
| `archive_verify` | Finished ZIP size and SHA-256 calculation |
| `archive_commit` | Saving ready state and its activity log atomically |
| `archive_cleanup` | Expiring superseded archives |

Each measured phase has `duration_ms`, `calls` and `failures`. Missing phases did
not run. Phase totals exclude other job overhead and should not be treated as a
complete breakdown of every instruction. A handled cleanup error can produce a
phase failure while the attempt's outcome remains `ready`.

Counters include copied source files/bytes, generated temporary files/bytes,
activity rows and total ZIP entries. Generated-file counters exclude the inline
JSON and README; ZIP entry count includes them. `bytes` is the finished archive
size. `process_peak_memory_bytes` is the worker process peak and can include earlier
attempts; it is not an isolated peak for this export.

Failed attempts also emit metrics before normal queue retry handling. Logging
failure cannot break a valid archive or replace the original export exception.
The service's optional `ArchivePerformance` argument allows read-only CLI export
checks to inspect timings without creating an archive database record.
