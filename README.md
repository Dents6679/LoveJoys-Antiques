# Lovejoy's Antiques Evaluation Platform
A Secure Antique evaluation website, created as part of a computer security module's coursework.
## Introduction
This platform is designed for the secure evaluation and listing of antique items. Aesthetics aren't important here, just security features.

## Features

- **User Registration**: Users can register with email verification, security questions, and 2FA options.
- **Secure Login**: Includes reCAPTCHA v3, session tokens, and security question or 2FA verification.
- **Password Policy**: Enforces strong password standards with salting and hashing.
- **Evaluation Requests**: Users can submit items for evaluation with image upload capabilities.
- **Admin Dashboard**: Admins can view and manage evaluation requests.

## Security Measures

- **SQL Injection Protection**: Prepared statements and parameter binding.
- **XSS Prevention**: Sanitization of all user inputs.
- **CSRF Protection**: Implementation of CSRF tokens in forms.
- **Spam Prevention**: Rate limiting and IP blocking after incorrect login attempts.
- **File Upload Security**: Restriction on file types and sizes, with random high-entropy naming for stored files.

## Getting Started
1. Visit [Lovejoy's Antiques](https://matriarchal-balls.000webhostapp.com/).
2. Register an account.
3. Verify your email and explore the features of the platform.



## Sample Account

  - Email: `rgpnhxbbcoytnfxclq@cazlp.com`
  - Password: `q%9b@d)RR<;[?2G3n<$j?`
  - Security Question Answer: `Computer Science`

## More Information
Please note that the platform's servers may sometimes be unreliable, and any `ERR_CONNECTION_TIMED_OUT` errors are not due to the code.



