@section('seo')
    <x-seo-meta
        title="Privacy Policy — EU VAT Info"
        description="Privacy policy for EU VAT Info website and mobile apps. Learn how we collect, use, and protect your data."
        type="website" />
@endsection

<div>
    <x-page-header title="Privacy policy" description="How EU VAT Info collects, uses and protects information when you use the website and apps." eyebrow="Legal" :breadcrumbs="['Privacy policy' => '']">
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 rounded-control border border-line bg-surface-subtle px-3 py-1.5 text-sm text-ink-muted">
                <x-ui.icon name="clock" class="size-4" />
                <span>Last updated <time class="tabular" datetime="2025-04-10">10 April 2025</time></span>
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="app-container py-8 sm:py-10">
        <div class="app-surface max-w-4xl p-5 sm:p-10">
            <div class="app-prose max-w-none">

                {{-- 1. Introduction --}}
                <h2>1. Introduction</h2>
                <p>
                    EU VAT Info ("we", "us", or "our") operates the website
                    <a href="https://vat.businesspress.io">vat.businesspress.io</a> and the EU VAT Info mobile applications
                    for iOS and Android (collectively, the "Service"). This Privacy Policy explains how we collect,
                    use, disclose, and safeguard your information when you use our Service.
                </p>
                <p>
                    By using the Service, you agree to the collection and use of information in accordance with this policy.
                    If you do not agree, please do not use the Service.
                </p>

                {{-- 2. Information We Collect --}}
                <h2>2. Information We Collect</h2>

                <h3>2.1 Information You Provide</h3>
                <ul>
                    <li><strong>VAT Numbers:</strong> When you use our VAT number validation tool, you submit VAT numbers for verification against the EU VIES system. We cache validation results temporarily to improve performance.</li>
                    <li><strong>Calculator Inputs:</strong> Amounts and VAT rates you enter into our calculator tools are processed locally and are not stored on our servers.</li>
                    <li><strong>Contact Information:</strong> If you contact us via email, we collect your email address and message content to respond to your inquiry.</li>
                </ul>

                <h3>2.2 Information Collected Automatically</h3>
                <ul>
                    <li><strong>Usage Data:</strong> We collect anonymized analytics data including pages visited, features used, country selections, and interaction patterns to improve our Service.</li>
                    <li><strong>Device Information:</strong> Browser type, operating system, device type, and screen resolution.</li>
                    <li><strong>IP Address:</strong> Used for approximate geolocation (country-level) to provide localized content and for security purposes. IP addresses are not stored long-term.</li>
                    <li><strong>Cookies:</strong> We use essential cookies for session management and language preferences. No third-party tracking cookies are used.</li>
                </ul>

                <h3>2.3 Mobile App Specific Data</h3>
                <ul>
                    <li><strong>Device Identifiers:</strong> Anonymous device identifiers for crash reporting and analytics.</li>
                    <li><strong>App Usage:</strong> Feature usage patterns, screen views, and interaction data to improve the app experience.</li>
                    <li><strong>Network Information:</strong> Connection type (Wi-Fi/cellular) to optimize data loading.</li>
                    <li><strong>No Location Data:</strong> Our mobile apps do not access your precise GPS location.</li>
                    <li><strong>No Camera or Microphone:</strong> Our apps do not access your camera or microphone.</li>
                    <li><strong>No Contacts or Phone Data:</strong> Our apps do not access your contacts, call logs, or phone data.</li>
                </ul>

                {{-- 3. How We Use Your Information --}}
                <h2>3. How We Use Your Information</h2>
                <p>We use the collected information for the following purposes:</p>
                <ul>
                    <li>Providing and maintaining the Service, including VAT rate information, calculators, and validation tools</li>
                    <li>Improving and optimizing the Service based on usage patterns</li>
                    <li>Providing localized content in your preferred language</li>
                    <li>Caching VAT validation results to reduce load on the EU VIES system and improve response times</li>
                    <li>Detecting and preventing abuse or unauthorized use of our API</li>
                    <li>Responding to your inquiries and support requests</li>
                    <li>Generating aggregated, anonymized statistics about VAT rates and usage trends</li>
                </ul>

                {{-- 4. Data Sharing and Disclosure --}}
                <h2>4. Data Sharing and Disclosure</h2>
                <p>We do <strong>not</strong> sell, trade, or rent your personal information to third parties. We may share information in the following limited circumstances:</p>
                <ul>
                    <li><strong>VIES Validation:</strong> VAT numbers submitted for validation are forwarded to the European Commission's VIES service, which is operated by the EU. This is necessary to provide the validation functionality.</li>
                    <li><strong>Hosting Providers:</strong> Our Service is hosted on infrastructure provided by third-party hosting services that may process data on our behalf under strict data processing agreements.</li>
                    <li><strong>Legal Requirements:</strong> We may disclose information if required by law, regulation, or legal process.</li>
                </ul>

                {{-- 5. Data Retention --}}
                <h2>5. Data Retention</h2>
                <ul>
                    <li><strong>VAT Validation Cache:</strong> Cached validation results are retained for up to 24 hours to optimize performance.</li>
                    <li><strong>Analytics Data:</strong> Anonymized usage statistics are retained for up to 12 months.</li>
                    <li><strong>Contact Inquiries:</strong> Email correspondence is retained for as long as necessary to resolve your inquiry, up to a maximum of 24 months.</li>
                </ul>

                {{-- 6. Data Security --}}
                <h2>6. Data Security</h2>
                <p>
                    We implement appropriate technical and organizational measures to protect your information, including:
                </p>
                <ul>
                    <li>HTTPS/TLS encryption for all data in transit</li>
                    <li>Regular security updates and vulnerability assessments</li>
                    <li>Access controls and authentication for administrative systems</li>
                    <li>Encrypted database connections</li>
                </ul>
                <p>
                    While we strive to protect your information, no method of electronic transmission or storage is 100% secure.
                    We cannot guarantee absolute security.
                </p>

                {{-- 7. Your Rights (GDPR) --}}
                <h2>7. Your Rights</h2>
                <p>
                    Under the General Data Protection Regulation (GDPR) and applicable data protection laws,
                    you have the following rights:
                </p>
                <ul>
                    <li><strong>Right of Access:</strong> Request a copy of the personal data we hold about you.</li>
                    <li><strong>Right to Rectification:</strong> Request correction of inaccurate personal data.</li>
                    <li><strong>Right to Erasure:</strong> Request deletion of your personal data ("right to be forgotten").</li>
                    <li><strong>Right to Restrict Processing:</strong> Request that we limit how we use your data.</li>
                    <li><strong>Right to Data Portability:</strong> Request your data in a structured, machine-readable format.</li>
                    <li><strong>Right to Object:</strong> Object to processing of your personal data for certain purposes.</li>
                </ul>
                <p>
                    To exercise any of these rights, please contact us at
                    <a href="mailto:privacy@businesspress.io">privacy@businesspress.io</a>.
                    We will respond to your request within 30 days.
                </p>

                {{-- 8. Cookies --}}
                <h2>8. Cookies</h2>
                <p>Our website uses the following types of cookies:</p>
                <ul>
                    <li><strong>Essential Cookies:</strong> Required for the Service to function (session management, CSRF protection, language preferences).</li>
                    <li><strong>Performance Cookies:</strong> Anonymized analytics to understand how visitors use our site and improve the experience.</li>
                </ul>
                <p>We do not use advertising or third-party tracking cookies. You can control cookies through your browser settings.</p>

                {{-- 9. Third-Party Services --}}
                <h2>9. Third-Party Services</h2>
                <p>Our Service integrates with the following third parties:</p>
                <ul>
                    <li><strong>European Commission VIES:</strong> For VAT number validation. Subject to the <a href="https://ec.europa.eu/info/legal-notice_en" target="_blank" rel="noopener">EU legal notice<x-ui.new-tab /></a>.</li>
                    <li><strong>DeepL:</strong> For automated translation of interface elements. No personal data is shared with DeepL.</li>
                </ul>

                {{-- 10. Children's Privacy --}}
                <h2>10. Children's Privacy</h2>
                <p>
                    Our Service is not directed to children under the age of 16. We do not knowingly collect
                    personal information from children. If you believe we have collected data from a child,
                    please contact us and we will promptly delete it.
                </p>

                {{-- 11. International Data Transfers --}}
                <h2>11. International Data Transfers</h2>
                <p>
                    Our Service is primarily operated within the European Union. If data is transferred outside the EU/EEA,
                    we ensure appropriate safeguards are in place in compliance with GDPR, such as Standard Contractual Clauses.
                </p>

                {{-- 12. API Usage --}}
                <h2>12. API Usage</h2>
                <p>
                    If you use our public API, we log API requests including your API key (if applicable), IP address,
                    and request parameters for rate limiting, abuse prevention, and service improvement.
                    API logs are retained for up to 30 days.
                </p>

                {{-- 13. Changes to This Policy --}}
                <h2>13. Changes to This Policy</h2>
                <p>
                    We may update this Privacy Policy from time to time. Changes will be posted on this page
                    with an updated "Last updated" date. We encourage you to review this policy periodically.
                    Continued use of the Service after changes constitutes acceptance of the updated policy.
                </p>

                {{-- 14. Contact Us --}}
                <h2>14. Contact Us</h2>
                <p>If you have questions about this Privacy Policy or our data practices, please contact us:</p>
                <ul>
                    <li><strong>Email:</strong> <a href="mailto:privacy@businesspress.io">privacy@businesspress.io</a></li>
                    <li><strong>Website:</strong> <a href="https://vat.businesspress.io">vat.businesspress.io</a></li>
                </ul>

            </div>
        </div>
    </div>
</div>
