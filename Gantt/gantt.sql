WITH status_periods AS (
SELECT
	bh.bug_id,
	bh.date_modified AS status_start,
	LEAD(bh.date_modified) OVER (PARTITION BY bh.bug_id
ORDER BY
	bh.date_modified) AS status_end,
	bh.new_value AS status_value,
	CASE
		bh.new_value
            WHEN '10' THEN '新建'
		WHEN '20' THEN '反馈'
		WHEN '30' THEN '认可'
		WHEN '40' THEN '已确认'
		WHEN '50' THEN '已分配'
		WHEN '80' THEN '已解决'
		WHEN '90' THEN '已关闭'
		ELSE bh.new_value
	END AS status_name
FROM
	mantis_bug_history_table bh
WHERE
	bh.field_name = 'status'
),
handler_periods AS (
SELECT
	bh.bug_id,
	bh.date_modified AS handler_start,
	LEAD(bh.date_modified) OVER (PARTITION BY bh.bug_id
ORDER BY
	bh.date_modified) AS handler_end,
	CAST(bh.new_value AS UNSIGNED) AS handler_id,
	u.realname AS handler_name
FROM
	mantis_bug_history_table bh
LEFT JOIN mantis_user_table u ON
	CAST(bh.new_value AS UNSIGNED) = u.id
WHERE
	bh.field_name = 'handler_id'
)
SELECT
	s.bug_id,
	b.summary,
	FROM_UNIXTIME(GREATEST(s.status_start, h.handler_start)) AS start_date,
	FROM_UNIXTIME(LEAST(
        COALESCE(s.status_end, UNIX_TIMESTAMP()), 
        COALESCE(h.handler_end, UNIX_TIMESTAMP())
    )) AS end_date,
	s.status_value,
	s.status_name,
	h.handler_id,
	h.handler_name
FROM
	status_periods s
INNER JOIN handler_periods h ON
	s.bug_id = h.bug_id
	AND s.status_start < COALESCE(h.handler_end, UNIX_TIMESTAMP())
	AND COALESCE(s.status_end, UNIX_TIMESTAMP()) > h.handler_start
LEFT JOIN mantis_bug_table b ON s.bug_id = b.id
ORDER BY
	s.bug_id,
	GREATEST(s.status_start, h.handler_start)
	

