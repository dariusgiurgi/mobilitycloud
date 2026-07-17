<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMobilityCloudTestMail extends Command
{
    protected $signature = 'mobilitycloud:mail-test
        {email : Recipient email address}
        {--subject=MobilityCloud mail test : Message subject}';

    protected $description = 'Send a simple operational test email using the configured mailer';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        $validator = validator(['email' => $email], ['email' => ['required', 'email']]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        try {
            Mail::raw(
                "This is a MobilityCloud operational mail test.\n\nSent at: ".now()->toDateTimeString()."\nEnvironment: ".app()->environment()."\n",
                fn ($message) => $message
                    ->to($email)
                    ->subject((string) $this->option('subject'))
            );

            $this->info('Test email sent to '.$email.'.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Mail test failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
