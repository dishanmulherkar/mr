<?php

class BatchStatusModel
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

     public function getBatchNumbers()
        {
            $result = mysqli_query($this->con,"
                SELECT DISTINCT batch_no
                FROM product_batches
                WHERE batch_no REGEXP '^[A-Z][0-9]{3}$'
                ORDER BY batch_no ASC
            ");

            $batchNos = [];

            while($row = mysqli_fetch_assoc($result))
            {
                $batchNos[] = $row['batch_no'];
            }

            return $batchNos;
        }

        public function getAll()
        {
                return mysqli_query($this->con, "
                    SELECT DISTINCT
                        pb.batch_no,
                        pb.status,
                        pb.state_id,
                        s.state_name
                    FROM product_batches pb
                    INNER JOIN state s ON pb.state_id = s.state_id
                    WHERE pb.batch_no REGEXP '^[A-Z][0-9]{3}$'
                    ORDER BY pb.batch_no ASC
                ");
        }

        public function store($post)
        {
            $batch_no = mysqli_real_escape_string($this->con, $post['batch_no']);
            $status   = mysqli_real_escape_string($this->con, $post['status']);

            if($status == '0')
            {
                $query = mysqli_query($this->con,"
                    UPDATE product_batches
                    SET status='0'
                    WHERE batch_no='$batch_no'
                ");
            }
            else
            {
                $query = mysqli_query($this->con,"
                    UPDATE product_batches
                    SET status='1'
                    WHERE batch_no='$batch_no'
                ");
            }

            return $query ? true : false;
        }
}