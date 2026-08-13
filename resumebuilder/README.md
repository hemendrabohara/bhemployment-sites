# Local Resume Builder

A minimal, fast, privacy-first localhost application that formats a beautiful resume template and securely exports it as an ATS-friendly, text-selectable PDF.

## Features
- **Split-Screen Design**: Type your information into the handy form on the left.
- **Direct Preview Editing**: Click directly onto any text on the right-side layout to edit it immediately.
- **Two-way Sync**: Form edits appear on the layout, and layout edits update the form.
- **Perfect Native PDFs**: Outputs via the native print dialog to guarantee your PDF is correctly sized and text-selectable (which Applicant Tracking Systems require).

## How to Run

To run this application, you must use a standard local HTTP server rather than opening `index.html` directly, because modern browsers enforce security limits on local `file:///` files.

### Using Python (Easiest - built into macOS)
1. Open your **Terminal**.
2. Navigate to where you saved this folder:
   ```bash
   cd "/Users/hemendra/Public/resume maker"
   ```
3. Start the built-in Python web server by typing:
   ```bash
   python3 -m http.server 8000
   ```
4. Open your favorite web browser (Chrome/Safari) and go to: 
   👉 [http://localhost:8000](http://localhost:8000)

### Using Node.js (npx)
If you are a web developer with Node.js installed, you can simply run:
```bash
npx serve .
```
And navigate to the local link it provides.

## Downloading the PDF
Once you're completely satisfied with how it looks:
1. Click the **"Download PDF"** button.
2. Your browser will display the Print Menu. 
3. Under the **"Printer"** or **"Destination"** dropdown, pick **Save as PDF**.
4. Click Save! The document defaults to cleanly disabling all browser headers, URLs, and page numbers.
