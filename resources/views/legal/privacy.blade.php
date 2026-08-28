@extends('layouts.legal', ['title' => 'Privacy Policy'])

@section('content')
    <section>
        <p class="text-sm text-gray-600 leading-relaxed">
            This policy explains how <strong>Silverback Customs UK Ltd</strong>, trading as <strong>ValeCheck</strong>
            ("we", "us", "our"), registered office at Unit 2A, 35 Eastgate North, Driffield, YO25 6DG, collects and
            uses personal data when you use this site. We are the data controller for the personal data described
            below.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">1. What we collect</h2>
        <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
            <li>Account details: name and email address</li>
            <li>The vehicle registration(s) you enter and any listing details you provide (mileage, asking price, listing URL)</li>
            <li>Payment confirmation from Stripe (we do not store your card details ourselves)</li>
            <li>Technical data: IP address, browser type, and pages visited, for security and to keep the site working correctly</li>
        </ul>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">2. How we use it</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            We use your data to create your account, generate the vehicle report you've paid for, email you a copy
            of it, process payment, prevent fraud, and respond if you contact us. We do this on the basis that it's
            necessary to perform our contract with you, or our legitimate interest in running and securing the
            service.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">3. Who we share it with</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            The vehicle registration you enter is sent to One Auto API to retrieve vehicle history, MOT and
            valuation data — this data is sourced from providers including Experian, the DVLA and the DVSA. We use
            Stripe to process payments, Postmark to send emails, and a cloud storage provider to store generated
            report PDFs. We don't sell your personal data to anyone.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">4. How long we keep it</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Report content (the underlying vehicle data and any generated PDF) is automatically and permanently
            deleted after the retention period shown on the report itself. Your account and payment records are kept
            for as long as your account is active and as required by law (for example, tax and accounting rules).
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">5. Your rights</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Under UK data protection law you have the right to access, correct, delete, or request a copy of your
            personal data, and to object to or restrict certain uses of it. To exercise any of these rights, contact
            us using the details below. If you're unhappy with how we've handled your data, you can also complain to
            the Information Commissioner's Office (ico.org.uk).
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">6. Cookies</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            We use essential cookies to keep you logged in and to protect the site against cross-site request
            forgery. We don't use advertising or tracking cookies.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">7. Security</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            We take reasonable technical and organisational measures to protect your personal data, including
            encrypting data in transit and restricting access to what's needed to run the service.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">8. Changes to this policy</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            We may update this policy from time to time. Significant changes will be reflected here with an updated
            date at the top of this page.
        </p>
    </section>

    <section>
        <h2 class="font-display font-bold text-lg text-vale-navy mb-2">9. Contact us</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Silverback Customs UK Ltd, trading as ValeCheck<br>
            Unit 2A, 35 Eastgate North, Driffield, YO25 6DG
        </p>
    </section>
@endsection
