#!/usr/bin/env python3
"""
ACR122U NFC Reader Integration Script
Connects to ACR122U reader and sends card data to Laravel API
"""

import requests
import json
import time
import sys
from smartcard.System import readers
from smartcard.util import toHexString, toBytes

class ACR122UReader:
    def __init__(self, api_url="http://localhost:8000"):
        self.api_url = api_url
        self.device_id = "acr122u_main"
        self.connection = None
        self.reader = None
        
    def connect(self):
        """Connect to ACR122U reader"""
        try:
            # Get available readers
            r = readers()
            
            if not r:
                print("❌ No smart card readers found!")
                return False
            
            print(f"📱 Found {len(r)} reader(s):")
            for i, reader in enumerate(r):
                print(f"   {i+1}. {reader}")
            
            # Use the first reader (usually ACR122U)
            self.reader = r[0]
            print(f"🔗 Reader selected: {self.reader}")
            
            # Don't connect immediately - we'll connect when needed
            self.connection = None
            
            print("✅ Reader initialized successfully!")
            return True
            
        except Exception as e:
            print(f"❌ Connection failed: {e}")
            return False
    
    def get_card_uid(self):
        """Read card UID from ACR122U"""
        try:
            # Create connection if not exists
            if not self.connection:
                self.connection = self.reader.createConnection()
                self.connection.connect()
            
            # APDU command to get UID
            get_uid = [0xFF, 0xCA, 0x00, 0x00, 0x04]
            
            # Send command
            response, sw1, sw2 = self.connection.transmit(get_uid)
            
            if sw1 == 0x90 and sw2 == 0x00:
                # Convert response to hex string
                uid = toHexString(response).replace(' ', '').upper()
                return uid
            else:
                # Card not present or error
                return None
                
        except Exception as e:
            # Card not present or communication error
            # Reset connection for next attempt
            self.connection = None
            return None
    
    def send_to_api(self, card_uid):
        """Send card data to Laravel API"""
        try:
            data = {
                'card_uid': card_uid,
                'device_id': self.device_id
            }
            
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            
            response = requests.post(
                f"{self.api_url}/rfid/tap",
                json=data,
                headers=headers,
                timeout=5
            )
            
            if response.status_code == 200:
                result = response.json()
                print(f"✅ {result.get('message', 'Success')}")
                return True
            else:
                print(f"❌ API Error: {response.status_code}")
                if response.text:
                    try:
                        error = response.json()
                        print(f"   {error.get('message', 'Unknown error')}")
                    except:
                        print(f"   {response.text}")
                return False
                
        except requests.exceptions.RequestException as e:
            print(f"❌ Network error: {e}")
            return False
    
    def run(self):
        """Main loop to continuously read cards"""
        if not self.connect():
            return
        
        print("\n🎯 Ready to read cards!")
        print("📋 Place a card on the reader to check in/out")
        print("🛑 Press Ctrl+C to exit")
        print("🔧 DEBUG: Running UPDATED script version\n")
        
        card_present = False
        last_uid = None
        
        try:
            while True:
                try:
                    # Try to get card UID
                    uid = self.get_card_uid()
                    
                    if uid and not card_present:
                        # New card detected
                        print(f"\n📱 Card detected: {uid}")
                        
                        # Send to API
                        success = self.send_to_api(uid)
                        
                        if success:
                            card_present = True
                            last_uid = uid
                        
                        print("📋 Waiting for card removal...\n")
                    
                    elif not uid and card_present:
                        # Card was removed
                        print("📤 Card removed - ready for next card\n")
                        card_present = False
                        last_uid = None
                    
                    time.sleep(0.5)  # Small delay
                    
                except Exception as e:
                    # Silent error handling
                    time.sleep(0.5)
                    
        except KeyboardInterrupt:
            print("\n\n👋 Shutting down RFID reader...")
            if self.connection:
                self.connection.disconnect()
            print("✅ Disconnected from ACR122U")

def main():
    """Main function"""
    print("🚀 ACR122U NFC Reader Integration")
    print("=" * 40)
    
    # Check if pyscard is installed
    try:
        import smartcard
    except ImportError:
        print("❌ Error: pyscard library not found!")
        print("📦 Install it with: pip install pyscard")
        return
    
    # Check if requests is installed
    try:
        import requests
    except ImportError:
        print("❌ Error: requests library not found!")
        print("📦 Install it with: pip install requests")
        return
    
    # Load configuration from file
    try:
        with open('rfid_config.json', 'r') as f:
            config = json.load(f)
        api_url = config['api']['url']
        print(f"📋 Loaded config: API URL = {api_url}")
    except (FileNotFoundError, KeyError, json.JSONDecodeError) as e:
        print(f"⚠️  Config file error: {e}")
        print("🌐 Using default API URL: http://localhost:8000")
        api_url = "http://localhost:8000"
    
    # Create and run reader
    reader = ACR122UReader(api_url)
    reader.run()

if __name__ == "__main__":
    main()
