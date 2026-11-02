# Sample Infrastructure Service
class SampleService:
    def process_file(self, file_path: str) -> str:
        # Simulate file processing
        with open(file_path, 'r') as f:
            return f.read().upper()

    def send_notification(self, message: str) -> None:
        # Simulate sending notification
        print(f"Notification sent: {message}")