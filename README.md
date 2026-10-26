# Processing Queue Messages using SQS

PHP worker and dispatcher for marketing email jobs stored in Amazon SQS.

## Setup

1. Install dependencies with `composer install`.
2. Create the MySQL database and schema using `php init-db.php`.
3. Configure the environment variables below. AWS credentials are loaded through the AWS SDK profile named by `AWS_PROFILE`; do not put access keys in this repository.
4. Set `SQS_QUEUE_URL` to your queue URL and set `MAILTRAP_USERNAME` and `MAILTRAP_PASSWORD` for SMTP delivery.

| Variable | Default | Used for |
| --- | --- | --- |
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_NAME` | `marketing_lab` | MySQL database |
| `DB_USER` | `root` | MySQL user |
| `DB_PASSWORD` | empty | MySQL password |
| `AWS_PROFILE` | `default` | AWS shared credentials profile |
| `AWS_REGION` | `ap-southeast-2` | AWS region |
| `SQS_QUEUE_URL` | required | SQS queue URL |
| `MAILTRAP_HOST` | `sandbox.smtp.mailtrap.io` | Mailtrap SMTP host |
| `MAILTRAP_PORT` | `2525` | Mailtrap SMTP port |
| `MAILTRAP_USERNAME` | required | Mailtrap SMTP username |
| `MAILTRAP_PASSWORD` | required | Mailtrap SMTP password |
| `MAIL_FROM_ADDRESS` | `marketing@example.com` | Sender address |
| `MAILTRAP_DELAY_SECONDS` | `11` | Delay between sent messages |

Run `php dispatch-queue.php [campaign-id]` to enqueue pending emails and `php work-queue.php` to process messages.

Local dependencies and environment files are excluded from Git.