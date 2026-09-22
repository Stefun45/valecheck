import UIKit
import AVFoundation
import Vision

/// Live camera view that runs on-device text recognition (Vision, no
/// network call) on every frame, looking for text shaped like a UK
/// registration plate. Requires two consecutive matching reads before
/// accepting a result - a single frame's OCR on a moving or angled
/// plate is noisy enough that one hit alone isn't trustworthy to
/// launch a real lookup.
final class PlateScannerViewController: UIViewController {
    var onResult: ((String?) -> Void)?

    private let session = AVCaptureSession()
    private let videoOutput = AVCaptureVideoDataOutput()
    private var previewLayer: AVCaptureVideoPreviewLayer?
    private let sequenceHandler = VNSequenceRequestHandler()
    private let processingQueue = DispatchQueue(label: "com.valecheck.platescanner.processing")

    private var lastCandidate: String?
    private var candidateStreak = 0
    private let requiredStreak = 2

    private let overlayLabel = UILabel()
    private let cancelButton = UIButton(type: .system)
    private var hasResolved = false

    override func viewDidLoad() {
        super.viewDidLoad()
        view.backgroundColor = .black
        configureCamera()
        configureOverlay()
    }

    override func viewDidAppear(_ animated: Bool) {
        super.viewDidAppear(animated)
        processingQueue.async { [session] in
            session.startRunning()
        }
    }

    override func viewWillDisappear(_ animated: Bool) {
        super.viewWillDisappear(animated)
        processingQueue.async { [session] in
            session.stopRunning()
        }
    }

    override func viewDidLayoutSubviews() {
        super.viewDidLayoutSubviews()
        previewLayer?.frame = view.bounds
    }

    private func configureCamera() {
        guard let device = AVCaptureDevice.default(.builtInWideAngleCamera, for: .video, position: .back),
              let input = try? AVCaptureDeviceInput(device: device),
              session.canAddInput(input) else {
            finish(with: nil)
            return
        }

        session.beginConfiguration()
        session.sessionPreset = .hd1920x1080
        session.addInput(input)

        videoOutput.setSampleBufferDelegate(self, queue: processingQueue)
        videoOutput.videoSettings = [kCVPixelBufferPixelFormatTypeKey as String: kCVPixelFormatType_32BGRA]
        if session.canAddOutput(videoOutput) {
            session.addOutput(videoOutput)
        }
        session.commitConfiguration()

        let layer = AVCaptureVideoPreviewLayer(session: session)
        layer.videoGravity = .resizeAspectFill
        layer.frame = view.bounds
        view.layer.addSublayer(layer)
        previewLayer = layer
    }

    private func configureOverlay() {
        overlayLabel.translatesAutoresizingMaskIntoConstraints = false
        overlayLabel.textColor = .white
        overlayLabel.font = .systemFont(ofSize: 15, weight: .medium)
        overlayLabel.textAlignment = .center
        overlayLabel.numberOfLines = 0
        overlayLabel.text = "Line up the number plate in view"
        overlayLabel.backgroundColor = UIColor.black.withAlphaComponent(0.55)
        overlayLabel.layer.cornerRadius = 8
        overlayLabel.clipsToBounds = true
        view.addSubview(overlayLabel)

        cancelButton.translatesAutoresizingMaskIntoConstraints = false
        cancelButton.setTitle("Cancel", for: .normal)
        cancelButton.setTitleColor(.white, for: .normal)
        cancelButton.addTarget(self, action: #selector(cancelTapped), for: .touchUpInside)
        view.addSubview(cancelButton)

        NSLayoutConstraint.activate([
            overlayLabel.leadingAnchor.constraint(equalTo: view.safeAreaLayoutGuide.leadingAnchor, constant: 24),
            overlayLabel.trailingAnchor.constraint(equalTo: view.safeAreaLayoutGuide.trailingAnchor, constant: -24),
            overlayLabel.bottomAnchor.constraint(equalTo: view.safeAreaLayoutGuide.bottomAnchor, constant: -40),

            cancelButton.topAnchor.constraint(equalTo: view.safeAreaLayoutGuide.topAnchor, constant: 16),
            cancelButton.trailingAnchor.constraint(equalTo: view.safeAreaLayoutGuide.trailingAnchor, constant: -16),
        ])
    }

    @objc private func cancelTapped() {
        finish(with: nil)
    }

    private func finish(with plate: String?) {
        guard !hasResolved else { return }
        hasResolved = true

        DispatchQueue.main.async { [weak self] in
            self?.onResult?(plate)
        }
    }
}

extension PlateScannerViewController: AVCaptureVideoDataOutputSampleBufferDelegate {
    func captureOutput(_ output: AVCaptureOutput, didOutput sampleBuffer: CMSampleBuffer, from connection: AVCaptureConnection) {
        guard !hasResolved, let pixelBuffer = CMSampleBufferGetImageBuffer(sampleBuffer) else { return }

        let request = VNRecognizeTextRequest { [weak self] request, _ in
            self?.handle(request: request)
        }
        request.recognitionLevel = .accurate
        request.usesLanguageCorrection = false

        try? sequenceHandler.perform([request], on: pixelBuffer, orientation: .right)
    }

    private func handle(request: VNRequest) {
        guard let observations = request.results as? [VNRecognizedTextObservation] else { return }

        let candidates = observations.compactMap { $0.topCandidates(1).first?.string }

        guard let plate = UKPlateMatcher.bestMatch(in: candidates) else {
            candidateStreak = 0
            lastCandidate = nil
            return
        }

        if plate == lastCandidate {
            candidateStreak += 1
        } else {
            lastCandidate = plate
            candidateStreak = 1
        }

        DispatchQueue.main.async { [weak self] in
            self?.overlayLabel.text = "Found \(plate) - hold steady"
        }

        if candidateStreak >= requiredStreak {
            finish(with: plate)
        }
    }
}
